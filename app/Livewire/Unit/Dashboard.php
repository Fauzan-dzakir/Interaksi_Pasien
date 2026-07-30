<?php

namespace App\Livewire\Unit;

use App\Enums\DeliveryOrderStatus;
use App\Enums\ItemBatchStatus;
use App\Enums\ZoneBucket;
use App\Models\DeliveryOrder;
use App\Models\ItemBatch;
use App\Models\Pickup;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Live tracking untuk unit — pengganti kebiasaan menelepon CSSD.
 * Diperbarui otomatis lewat wire:poll, tanpa perlu refresh manual.
 */
class Dashboard extends Component
{
    #[Url(as: 'zona', keep: false)]
    public string $zoneFilter = '';

    public function render()
    {
        $unitId = auth()->user()->unit_id;

        $activeBatches = ItemBatch::query()
            ->forUnit($unitId)
            ->active()
            ->with(['instrumentSet', 'item', 'currentDeliveryOrder'])
            ->orderBy('status_changed_at', 'desc')
            ->get();

        $tracked = collect(ZoneBucket::trackedByUnit())->map(fn (ZoneBucket $zone) => [
            'zone' => $zone,
            'count' => $activeBatches->filter(fn (ItemBatch $b) => $b->zone() === $zone)->count(),
        ]);

        $filtered = $this->zoneFilter
            ? $activeBatches->filter(fn (ItemBatch $b) => $b->zone()->value === $this->zoneFilter)
            : $activeBatches;

        return view('livewire.unit.dashboard', [
            'trackedZones' => $tracked,
            'batches' => $filtered->take(60),
            'totalActive' => $activeBatches->count(),
            'atUnitCount' => $activeBatches->filter(fn (ItemBatch $b) => $b->zone() === ZoneBucket::AtUnit)->count(),
            'awaitingIntake' => DeliveryOrder::forUnit($unitId)->awaitingIntake()->count(),
            'awaitingConfirm' => Pickup::forUnit($unitId)->pending()->count(),
            'readyCount' => $activeBatches->filter(
                fn (ItemBatch $b) => $b->status === ItemBatchStatus::ReadyForPickup
            )->count(),
            'recentOrders' => DeliveryOrder::forUnit($unitId)
                ->with('originUnit')
                ->whereIn('status', [
                    DeliveryOrderStatus::PendingCssdIntake,
                    DeliveryOrderStatus::IntakeRecorded,
                    DeliveryOrderStatus::Processing,
                ])
                ->latest('sent_at')
                ->limit(5)
                ->get(),
        ]);
    }
}
