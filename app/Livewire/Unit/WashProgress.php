<?php

namespace App\Livewire\Unit;

use App\Enums\AssetStatus;
use App\Enums\OrderStatus;
use App\Enums\ZoneBucket;
use App\Models\Asset;
use App\Models\Order;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Pemantauan progres pencucian, menggantikan katalog stok.
 *
 * Unit tidak lagi memilih alat dari gudang, jadi yang mereka butuhkan adalah
 * melihat sampai mana alat kirimannya diproses CSSD, plus riwayat pesanan lama.
 */
class WashProgress extends Component
{
    use WithPagination;

    #[Url(as: 'tahap', keep: false)]
    public string $zoneFilter = '';

    public function render()
    {
        $unitId = auth()->user()->unit_id;

        // Alat unit ini yang sedang berada di dalam siklus CSSD.
        $inProcess = Asset::query()
            ->whereHas('orders', fn ($q) => $q->where('orders.unit_id', $unitId)->whereIn('orders.status', [
                OrderStatus::Preparing->value,
                OrderStatus::ReadyForPickup->value,
                OrderStatus::Delivering->value,
            ]))
            ->with(['instrumentSet', 'item'])
            ->orderByDesc('status_changed_at')
            ->get();

        $tracked = collect(ZoneBucket::trackedByUnit())->map(fn (ZoneBucket $zone) => [
            'zone' => $zone,
            'count' => $inProcess->filter(fn (Asset $a) => $a->zone() === $zone)->count(),
        ]);

        $visible = $this->zoneFilter
            ? $inProcess->filter(fn (Asset $a) => $a->zone()->value === $this->zoneFilter)
            : $inProcess;

        return view('livewire.unit.wash-progress', [
            'zones' => $tracked,
            'assets' => $visible->take(60),
            'totalInProcess' => $inProcess->count(),
            'readyCount' => $inProcess->filter(
                fn (Asset $a) => in_array($a->status, [AssetStatus::ReadyForHandover, AssetStatus::InTransit], true)
            )->count(),

            'activeOrders' => Order::forUnit($unitId)->open()
                ->withCount('assets')->latest()->get(),

            'history' => Order::forUnit($unitId)
                ->whereIn('status', [OrderStatus::Received->value, OrderStatus::Cancelled->value])
                ->withCount('assets')
                ->latest('received_at')
                ->paginate(10),
        ]);
    }
}
