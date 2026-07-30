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

    #[Url(as: 'status', keep: false)]
    public string $statusFilter = '';

    #[Url(as: 'q', keep: false)]
    public string $search = '';

    public function resetFilters(): void
    {
        $this->reset(['zoneFilter', 'statusFilter', 'search']);
    }

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

        $filtered = $activeBatches
            ->when($this->zoneFilter, fn ($batches) => $batches->filter(fn (ItemBatch $b) => $b->zone()->value === $this->zoneFilter))
            ->when($this->statusFilter, fn ($batches) => $batches->filter(fn (ItemBatch $b) => $b->status->value === $this->statusFilter))
            ->when($this->search, function ($batches) {
                $term = mb_strtolower(trim($this->search));

                return $batches->filter(
                    fn (ItemBatch $b) => str_contains(mb_strtolower($b->public_code), $term)
                        || str_contains(mb_strtolower($b->displayName()), $term)
                );
            });

        return view('livewire.unit.dashboard', [
            'trackedZones' => $tracked,
            'statusOptions' => ItemBatchStatus::options(),
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
                    DeliveryOrderStatus::ReadyForDistribution,
                ])
                ->latest('sent_at')
                ->limit(5)
                ->get(),
        ]);
    }
}
