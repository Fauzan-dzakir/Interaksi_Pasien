<?php

namespace App\Livewire\Cssd;

use App\Enums\ItemBatchStatus;
use App\Enums\ZoneBucket;
use App\Models\DeliveryOrder;
use App\Models\ItemBatch;
use App\Models\Pickup;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        $counts = ItemBatch::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $zoneTotals = collect(ZoneBucket::trackedByUnit())->map(fn (ZoneBucket $zone) => [
            'zone' => $zone,
            'count' => collect($zone->statuses())
                ->sum(fn (ItemBatchStatus $s) => (int) ($counts[$s->value] ?? 0)),
        ]);

        return view('livewire.cssd.dashboard', [
            'zoneTotals' => $zoneTotals,
            // Rincian per tahap: menunjukkan di mana antrian menumpuk.
            'stageBreakdown' => collect(ItemBatchStatus::cases())
                ->filter(fn (ItemBatchStatus $s) => in_array($s->zone(), ZoneBucket::trackedByUnit(), true))
                ->map(fn (ItemBatchStatus $s) => [
                    'status' => $s,
                    'count' => (int) ($counts[$s->value] ?? 0),
                ]),
            'pendingIntake' => DeliveryOrder::awaitingIntake()->with('originUnit')
                ->orderByDesc('is_cito')->latest('sent_at')->get(),
            'pendingPickups' => Pickup::pending()->count(),
            'readyForDistribution' => (int) ($counts[ItemBatchStatus::InStorage->value] ?? 0),
            'expiredSterileCount' => ItemBatch::where('status', ItemBatchStatus::InStorage)
                ->whereNotNull('sterilization_expired_at')
                ->where('sterilization_expired_at', '<', now())
                ->count(),
        ]);
    }
}
