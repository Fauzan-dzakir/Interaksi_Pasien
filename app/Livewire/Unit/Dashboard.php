<?php

namespace App\Livewire\Unit;

use App\Enums\AssetStatus;
use App\Enums\OrderStatus;
use App\Enums\ZoneBucket;
use App\Models\Asset;
use App\Models\Order;
use Livewire\Component;

/**
 * Ringkasan untuk unit: apa yang perlu ditindaklanjuti, dan sampai mana
 * alat kiriman diproses CSSD.
 */
class Dashboard extends Component
{
    public function render()
    {
        $unitId = auth()->user()->unit_id;

        // Alat yang sedang di dalam siklus CSSD lewat pesanan unit ini.
        $inProcess = Asset::query()
            ->whereHas('orders', fn ($q) => $q->where('orders.unit_id', $unitId)->whereIn('orders.status', [
                OrderStatus::Preparing->value,
                OrderStatus::ReadyForPickup->value,
                OrderStatus::Delivering->value,
            ]))
            ->with(['instrumentSet', 'item'])
            ->get();

        // Alat yang saat ini dipegang unit, terlepas dari pesanan.
        $atUnit = Asset::query()
            ->whereIn('status', [AssetStatus::AtUnit, AssetStatus::InUse])
            ->where(fn ($q) => $q->whereNull('batch_id')
                ->orWhereHas('batch', fn ($b) => $b->where('unit_id', $unitId)))
            ->with(['instrumentSet', 'item'])
            ->get();

        return view('livewire.unit.dashboard', [
            'zones' => collect(ZoneBucket::trackedByUnit())->map(fn (ZoneBucket $zone) => [
                'zone' => $zone,
                'count' => $inProcess->filter(fn (Asset $a) => $a->zone() === $zone)->count(),
            ]),
            'inProcessCount' => $inProcess->count(),
            'atUnitCount' => $atUnit->count(),
            'inUseCount' => $atUnit->filter(fn (Asset $a) => $a->status === AssetStatus::InUse)->count(),

            'awaitingConfirm' => Order::forUnit($unitId)
                ->whereIn('status', [OrderStatus::ReadyForPickup->value, OrderStatus::Delivering->value])
                ->count(),
            'pendingIntake' => Order::forUnit($unitId)->where('status', OrderStatus::Pending)->count(),
            'openOrders' => Order::forUnit($unitId)->open()->withCount('assets')->latest()->limit(5)->get(),
            'recentAssets' => $inProcess->sortByDesc('status_changed_at')->take(8),
        ]);
    }
}
