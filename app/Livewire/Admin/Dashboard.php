<?php

namespace App\Livewire\Admin;

use App\Enums\ItemBatchStatus;
use App\Enums\UserRole;
use App\Enums\ZoneBucket;
use App\Models\DeliveryOrder;
use App\Models\InstrumentSet;
use App\Models\Item;
use App\Models\ItemBatch;
use App\Models\ItemBatchEvent;
use App\Models\Unit;
use App\Models\User;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        $statusCounts = ItemBatch::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('livewire.admin.dashboard', [
            'masterStats' => [
                ['label' => 'Unit Aktif', 'value' => Unit::active()->count(), 'route' => 'admin.units'],
                ['label' => 'Jenis Alat', 'value' => Item::active()->count(), 'route' => 'admin.items'],
                ['label' => 'Set Alat', 'value' => InstrumentSet::active()->count(), 'route' => 'admin.instrument-sets'],
                ['label' => 'Pengguna Aktif', 'value' => User::active()->count(), 'route' => 'admin.users'],
            ],
            'operationalStats' => [
                ['label' => 'Alat Dilacak', 'value' => ItemBatch::active()->count(), 'route' => 'admin.audit'],
                ['label' => 'Order Berjalan', 'value' => DeliveryOrder::open()->count(), 'route' => 'cssd.orders'],
                ['label' => 'Ditandai Hilang', 'value' => (int) ($statusCounts[ItemBatchStatus::Lost->value] ?? 0), 'alert' => true, 'route' => 'admin.audit', 'params' => ['status' => ItemBatchStatus::Lost->value]],
                ['label' => 'Koreksi Admin', 'value' => ItemBatchEvent::where('is_admin_override', true)->count(), 'route' => 'admin.audit'],
            ],
            'zoneTotals' => collect(ZoneBucket::trackedByUnit())->map(fn (ZoneBucket $zone) => [
                'zone' => $zone,
                'count' => collect($zone->statuses())
                    ->sum(fn (ItemBatchStatus $s) => (int) ($statusCounts[$s->value] ?? 0)),
                'route' => 'admin.audit',
                'params' => ['zona' => $zone->value],
            ]),
            'usersByRole' => collect(UserRole::cases())->map(fn (UserRole $role) => [
                'label' => $role->label(),
                'count' => User::active()->where('role', $role)->count(),
            ]),
        ]);
    }
}
