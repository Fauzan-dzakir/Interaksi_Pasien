<?php

namespace App\Livewire\Admin;

use App\Enums\AssetStatus;
use App\Enums\AssetType;
use App\Enums\UserRole;
use App\Enums\ZoneBucket;
use App\Models\Asset;
use App\Models\AssetEvent;
use App\Models\Batch;
use App\Models\InstrumentSet;
use App\Models\Item;
use App\Models\Order;
use App\Models\ReturnShipment;
use App\Models\Unit;
use App\Models\User;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        $statusCounts = Asset::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('livewire.admin.dashboard', [
            'operationalStats' => [
                ['label' => 'Aset Dilacak', 'value' => Asset::active()->count(), 'route' => 'admin.audit'],
                ['label' => 'Pesanan Berjalan', 'value' => Order::open()->count(), 'route' => null],
                ['label' => 'Kiriman Belum Dikonfirmasi', 'value' => ReturnShipment::pending()->count(), 'route' => null, 'alert' => true],
                ['label' => 'Set Tidak Lengkap', 'value' => Asset::where('asset_type', AssetType::Set)->where('is_complete', false)->count(), 'route' => 'admin.incomplete-sets', 'alert' => true],
                ['label' => 'Ditandai Hilang', 'value' => (int) ($statusCounts[AssetStatus::Lost->value] ?? 0), 'route' => 'admin.audit', 'alert' => true],
                ['label' => 'Koreksi Admin', 'value' => AssetEvent::where('is_admin_override', true)->count(), 'route' => 'admin.audit'],
                ['label' => 'Batch Aktif', 'value' => Batch::active()->count(), 'route' => null],
                ['label' => 'Stok Bebas', 'value' => Asset::inStock()->count(), 'route' => null],
            ],
            'zoneTotals' => collect(ZoneBucket::trackedByUnit())
                ->push(ZoneBucket::AtUnit)
                ->map(fn (ZoneBucket $zone) => [
                    'zone' => $zone,
                    'count' => collect(AssetStatus::cases())
                        ->filter(fn (AssetStatus $s) => $s->zone() === $zone)
                        ->sum(fn (AssetStatus $s) => (int) ($statusCounts[$s->value] ?? 0)),
                ]),
            'masterStats' => [
                ['label' => 'Unit Aktif', 'value' => Unit::active()->count(), 'route' => 'admin.units'],
                ['label' => 'Jenis Alat', 'value' => Item::active()->count(), 'route' => 'admin.items'],
                ['label' => 'Set Alat', 'value' => InstrumentSet::active()->count(), 'route' => 'admin.instrument-sets'],
                ['label' => 'Pengguna Aktif', 'value' => User::active()->count(), 'route' => 'admin.users'],
            ],
            'usersByRole' => collect(UserRole::cases())->map(fn (UserRole $role) => [
                'label' => $role->label(),
                'count' => User::active()->where('role', $role)->count(),
            ]),
        ]);
    }
}
