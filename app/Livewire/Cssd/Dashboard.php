<?php

namespace App\Livewire\Cssd;

use App\Enums\AssetStatus;
use App\Enums\AssetType;
use App\Models\Asset;
use App\Models\Order;
use App\Models\ReturnShipment;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        $counts = Asset::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $at = fn (AssetStatus $s) => (int) ($counts[$s->value] ?? 0);

        return view('livewire.cssd.dashboard', [
            // Dua antrian yang paling menahan alur kerja CSSD, ditaruh paling depan.
            'pendingOrders' => Order::awaitingPreparation()->with('unit')->latest()->get(),
            'pendingReturns' => ReturnShipment::pending()->with(['unit', 'sentBy'])
                ->withCount('assets')->latest('sent_at')->get(),

            'stageCounts' => [
                ['label' => 'Tersedia di Gudang', 'value' => $at(AssetStatus::Available), 'route' => 'cssd.stock'],
                ['label' => 'Disiapkan', 'value' => $at(AssetStatus::Reserved), 'route' => 'cssd.orders'],
                ['label' => 'Di Unit', 'value' => $at(AssetStatus::AtUnit) + $at(AssetStatus::InUse) + $at(AssetStatus::InTransit) + $at(AssetStatus::ReadyForHandover), 'route' => null],
                ['label' => 'Menunggu Konfirmasi', 'value' => $at(AssetStatus::ReturnPending), 'route' => 'cssd.returns'],
                ['label' => 'Pencucian', 'value' => $at(AssetStatus::Washing), 'route' => 'cssd.new-barcode'],
                ['label' => 'Bersih & Dikemas', 'value' => $at(AssetStatus::Packed), 'route' => 'cssd.scan'],
                ['label' => 'Sterilisasi', 'value' => $at(AssetStatus::Sterilizing), 'route' => 'cssd.scan'],
                ['label' => 'Set Tidak Lengkap', 'value' => Asset::where('asset_type', AssetType::Set)->where('is_complete', false)->count(), 'route' => null, 'alert' => true],
            ],
        ]);
    }
}
