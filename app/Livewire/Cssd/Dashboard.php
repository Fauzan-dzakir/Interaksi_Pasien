<?php

namespace App\Livewire\Cssd;

use App\Enums\ItemBatchStatus;
use App\Enums\ScanInputMethod;
use App\Enums\ZoneBucket;
use App\Models\DeliveryOrder;
use App\Models\ItemBatch;
use App\Models\Pickup;
use App\Services\DeliveryOrderService;
use App\Services\ItemBatchTransitionService;
use Livewire\Component;

class Dashboard extends Component
{
    /**
     * Tujuan tahap berikutnya untuk pemindahan massal LINTAS ORDER — dipakai di
     * dashboard karena secara fisik alat dari beberapa order sering dicuci/
     * dikeringkan bersamaan dalam satu bak/rak, bukan dipisah per order. Tombol
     * per-order di halaman detail order (OrderIntake) tetap ada untuk kasus
     * order tertentu (mis. CITO) yang perlu diproses terpisah dari yang lain.
     *
     * @return array<string, ItemBatchStatus>
     */
    private function bulkAdvanceTargets(): array
    {
        return [
            ItemBatchStatus::ReturnedDirty->value => ItemBatchStatus::DirtyZoneWashing,
            ItemBatchStatus::DirtyZoneWashing->value => ItemBatchStatus::DirtyZoneDrying,
            ItemBatchStatus::DirtyZoneDrying->value => ItemBatchStatus::CleanlinessCheckPending,
        ];
    }

    /**
     * Pindahkan sekaligus SEMUA alat di seluruh order yang masih berada di
     * $fromStatus ke tahap berikutnya — lintas order, sesuai cara kerja fisik
     * (satu bak cuci / satu rak pengering biasanya berisi alat dari beberapa
     * order sekaligus). Tiap alat tetap dapat jejak audit sendiri, dan setiap
     * order yang alatnya ikut berpindah otomatis disinkronkan statusnya.
     */
    public function advanceZoneGroup(string $fromStatus, ItemBatchTransitionService $transitions, DeliveryOrderService $orders): void
    {
        $this->authorize('advanceStage', ItemBatch::class);

        $targets = $this->bulkAdvanceTargets();

        if (! isset($targets[$fromStatus])) {
            return;
        }

        $target = $targets[$fromStatus];
        $user = auth()->user();

        $batches = ItemBatch::where('status', $fromStatus)->get();

        $moved = 0;
        $affectedOrderIds = [];

        foreach ($batches as $batch) {
            if ($transitions->transition($batch, $target, $user, ScanInputMethod::Manual, 'Tindakan massal lintas order — Dashboard CSSD')) {
                $moved++;

                if ($batch->current_delivery_order_id) {
                    $affectedOrderIds[$batch->current_delivery_order_id] = true;
                }
            }
        }

        foreach (array_keys($affectedOrderIds) as $orderId) {
            if ($order = DeliveryOrder::find($orderId)) {
                $orders->syncStatus($order, $user);
            }
        }

        session()->flash('status', $moved > 0
            ? "{$moved} alat dari ".count($affectedOrderIds)." order dipindah ke tahap \"{$target->label()}\"."
            : 'Tidak ada alat yang dipindahkan.');
    }

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
