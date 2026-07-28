<?php

namespace Database\Seeders;

use App\Enums\DeliveryMethod;
use App\Enums\ItemBatchStatus;
use App\Enums\ScanInputMethod;
use App\Enums\ScanStation;
use App\Enums\UserRole;
use App\Models\DeliveryOrder;
use App\Models\InstrumentSet;
use App\Models\Item;
use App\Models\Unit;
use App\Models\User;
use App\Services\DeliveryOrderService;
use App\Services\ItemBatchTransitionService;
use App\Services\PickupService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Membuat contoh order yang tersebar di berbagai tahap, supaya sistem langsung
 * terlihat hidup saat pertama dibuka untuk demo/uji coba.
 *
 * Jalankan terpisah: php artisan db:seed --class=DemoFlowSeeder
 * JANGAN dijalankan di lingkungan produksi RS.
 */
class DemoFlowSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(
        DeliveryOrderService $orders,
        ItemBatchTransitionService $transitions,
        PickupService $pickups,
    ): void {
        $cssd = User::where('role', UserRole::CssdStaff)->firstOrFail();

        // Tiap skenario berhenti di tahap berbeda supaya seluruh zona ada isinya.
        $scenarios = [
            ['unit' => 'IBS', 'stop' => null, 'lines' => [['set', 'SET-LAPARO', 2], ['item', 'ALT-001', 4]]],
            ['unit' => 'IBS', 'stop' => ScanStation::Drying, 'lines' => [['set', 'SET-MINOR', 1]]],
            ['unit' => 'IGD', 'stop' => ScanStation::CleanlinessPass, 'lines' => [['set', 'SET-HECTING', 3], ['item', 'ALT-010', 6]]],
            ['unit' => 'ICU', 'stop' => ScanStation::Sterilizing, 'lines' => [['item', 'ALT-011', 2], ['item', 'ALT-013', 3]]],
            ['unit' => 'IGD', 'stop' => ScanStation::StoragePass, 'lines' => [['set', 'SET-MINOR', 2]]],
            ['unit' => 'ICU', 'stop' => 'delivered', 'lines' => [['set', 'SET-HECTING', 1], ['item', 'ALT-005', 4]]],
        ];

        foreach ($scenarios as $index => $scenario) {
            $unit = Unit::where('code', $scenario['unit'])->firstOrFail();
            $nakes = User::where('unit_id', $unit->id)->where('role', UserRole::Nakes)->firstOrFail();

            $order = $orders->create($nakes, [
                'courier_name' => ['Budi Santoso', 'Sri Wahyuni', 'Agus Setiawan'][$index % 3],
                'sent_at' => now()->subHours(20 - ($index * 3)),
                'box_count' => 1 + ($index % 3),
                'notes' => $index === 2 ? 'Mohon didahulukan, jadwal operasi sore.' : null,
            ]);

            // Skenario pertama sengaja dibiarkan menunggu pendataan.
            if ($scenario['stop'] === null) {
                continue;
            }

            $orders->recordIntake($order, $cssd, collect($scenario['lines'])->map(fn (array $line) => [
                'line_type' => $line[0] === 'set' ? 'set' : 'individual',
                'instrument_set_id' => $line[0] === 'set' ? InstrumentSet::where('code', $line[1])->value('id') : null,
                'item_id' => $line[0] === 'item' ? Item::where('code', $line[1])->value('id') : null,
                'quantity' => $line[2],
                'notes' => null,
            ])->all());

            $this->advance($order->fresh(), $scenario['stop'], $cssd, $transitions, $orders, $pickups, $nakes);
        }

        $this->command?->info('Data demo alur CSSD berhasil dibuat.');
    }

    /** Mendorong seluruh alat pada satu order sampai tahap yang ditentukan. */
    private function advance(
        DeliveryOrder $order,
        ScanStation|string $stop,
        User $cssd,
        ItemBatchTransitionService $transitions,
        DeliveryOrderService $orders,
        PickupService $pickups,
        User $nakes,
    ): void {
        $sequence = [
            ScanStation::Washing, ScanStation::Drying, ScanStation::CleanlinessCheck,
            ScanStation::CleanlinessPass, ScanStation::Packaging, ScanStation::Sterilizing,
            ScanStation::SterileCheck, ScanStation::StoragePass,
        ];

        $goesAllTheWay = $stop === 'delivered';

        foreach ($sequence as $station) {
            foreach ($order->itemBatches as $batch) {
                $transitions->transition(
                    $batch,
                    $station->targetStatus(),
                    $cssd,
                    ScanInputMethod::HidScanner,
                    $station->label(),
                );
            }

            if (! $goesAllTheWay && $station === $stop) {
                break;
            }
        }

        $orders->syncStatus($order->fresh(), $cssd);

        if (! $goesAllTheWay) {
            return;
        }

        $pickup = $pickups->dispatch(
            staff: $cssd,
            unitId: $order->origin_unit_id,
            batchIds: $order->itemBatches->pluck('id')->all(),
            method: DeliveryMethod::DirectDelivery,
            receiverName: $nakes->name,
        );

        $pickups->confirmReceipt($pickup, $nakes);

        // Satu alat ditandai sedang dipakai agar zona "Di Unit" ada isinya.
        $first = $order->itemBatches()->where('status', ItemBatchStatus::PickedUp)->first();

        if ($first) {
            $transitions->transition(
                $first,
                ItemBatchStatus::InUse,
                $nakes,
                ScanInputMethod::QrCamera,
                'Penandaan Pemakaian Unit',
            );
        }
    }
}
