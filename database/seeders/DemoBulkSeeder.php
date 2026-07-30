<?php

namespace Database\Seeders;

use App\Enums\DeliveryMethod;
use App\Enums\ItemBatchStatus;
use App\Enums\ScanInputMethod;
use App\Enums\ScanStation;
use App\Enums\UserRole;
use App\Models\InstrumentSet;
use App\Models\Item;
use App\Models\ItemBatchUsageMark;
use App\Models\Unit;
use App\Models\User;
use App\Services\DeliveryOrderService;
use App\Services\ItemBatchTransitionService;
use App\Services\PickupService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Data demo TAMBAHAN dalam jumlah banyak, supaya web bisa dipakai untuk
 * simulasi/uji coba tanpa perlu setup lokal — dashboard, filter, dan menu baru
 * (Pendataan Alat di Unit, checklist "Rincian Alat yang Dikirim", dll) langsung
 * ada isinya untuk dicoba.
 *
 * TIDAK membuat user baru — semua pakai akun demo yang SUDAH ada (lihat
 * DatabaseSeeder), supaya kredensial login tidak berubah.
 *
 * Jalankan terpisah: php artisan db:seed --class=DemoBulkSeeder
 * Aman dijalankan berkali-kali (cuma menambah data baru, tidak menghapus).
 */
class DemoBulkSeeder extends Seeder
{
    use WithoutModelEvents;

    private const COURIERS = ['Budi Santoso', 'Sri Wahyuni', 'Agus Setiawan', 'Dewi Anggraini', 'Rudi Hartono'];

    private const ITEM_CODES = [
        'ALT-001', 'ALT-002', 'ALT-003', 'ALT-004', 'ALT-005', 'ALT-006',
        'ALT-007', 'ALT-008', 'ALT-009', 'ALT-010', 'ALT-011', 'ALT-012',
        'ALT-013', 'ALT-014', 'ALT-015', 'ALT-016',
    ];

    private const SET_CODES = ['SET-MINOR', 'SET-LAPARO', 'SET-HECTING'];

    private const STATIONS = [
        ScanStation::Washing, ScanStation::Drying, ScanStation::CleanlinessCheck,
        ScanStation::CleanlinessPass, ScanStation::Packaging, ScanStation::Sterilizing,
        ScanStation::SterileCheck, ScanStation::StoragePass,
    ];

    public function run(
        DeliveryOrderService $orders,
        ItemBatchTransitionService $transitions,
        PickupService $pickups,
    ): void {
        $cssd = User::where('role', UserRole::CssdStaff)->firstOrFail();
        $units = Unit::whereIn('code', ['IBS', 'IGD', 'ICU', 'RNP-A', 'POLI-GIGI'])->get()->keyBy('code');

        // ---- 22 order tersebar di berbagai unit & tahap ----
        $recipes = [
            ['IBS', 'processing', false], ['IBS', 'processing', true], ['IBS', 'ready', false],
            ['IBS', 'delivered', false], ['IBS', 'delivered', false], ['IBS', 'pending', false],
            ['IGD', 'processing', true], ['IGD', 'ready', false], ['IGD', 'delivered', false],
            ['IGD', 'pending', false], ['IGD', 'lost', false],
            ['ICU', 'processing', false], ['ICU', 'ready', false], ['ICU', 'delivered', false],
            ['ICU', 'pending', true], ['ICU', 'retired', false],
            ['RNP-A', 'processing', false], ['RNP-A', 'ready', false], ['RNP-A', 'delivered', false],
            ['RNP-A', 'pending', false],
            ['POLI-GIGI', 'processing', false], ['POLI-GIGI', 'delivered', false],
        ];

        foreach ($recipes as $index => [$unitCode, $stage, $isCito]) {
            $unit = $units[$unitCode];
            $nakes = User::where('unit_id', $unit->id)->where('role', UserRole::Nakes)->first();

            if (! $nakes) {
                continue;
            }

            $lines = $this->randomLines();

            $order = $orders->create($nakes, [
                'courier_name' => self::COURIERS[$index % count(self::COURIERS)],
                'sent_at' => now()->subHours(random_int(1, 72)),
                'box_count' => random_int(1, 4),
                'is_cito' => $isCito,
                'needed_at' => $isCito ? now()->addHours(random_int(1, 6)) : null,
                'notes' => $isCito ? 'Mohon didahulukan, jadwal operasi mendesak.' : null,
            ]);

            if ($stage === 'pending') {
                continue;
            }

            $orders->recordIntake($order, $cssd, $lines);
            $order->refresh();
            $batches = $order->itemBatches;

            if ($stage === 'processing') {
                // Berhenti di tahap acak zona kotor/bersih supaya semua zona ada isinya.
                $stopAt = random_int(1, 5);
                foreach (array_slice(self::STATIONS, 0, $stopAt) as $station) {
                    foreach ($batches as $batch) {
                        $transitions->transition($batch, $station->targetStatus(), $cssd, ScanInputMethod::HidScanner, $station->label());
                    }
                }
                $orders->syncStatus($order->fresh(), $cssd);

                continue;
            }

            if ($stage === 'lost' || $stage === 'retired') {
                // Sebagian alat diproses penuh, satu ditandai hilang/rusak lewat Koreksi Admin.
                foreach (self::STATIONS as $station) {
                    foreach ($batches as $batch) {
                        $transitions->transition($batch, $station->targetStatus(), $cssd, ScanInputMethod::HidScanner, $station->label());
                    }
                }

                $target = $stage === 'lost' ? ItemBatchStatus::Lost : ItemBatchStatus::Retired;
                $transitions->adminOverride(
                    $batches->first(),
                    $target,
                    $cssd,
                    $stage === 'lost' ? 'Tidak ditemukan saat stock opname rutin.' : 'Rusak permanen, tidak layak pakai lagi.',
                );

                $orders->syncStatus($order->fresh(), $cssd);

                continue;
            }

            // ready / delivered: semua batch lolos sampai gudang steril.
            foreach (self::STATIONS as $station) {
                foreach ($batches as $batch) {
                    $transitions->transition($batch, $station->targetStatus(), $cssd, ScanInputMethod::HidScanner, $station->label());
                }
            }
            $orders->syncStatus($order->fresh(), $cssd);

            if ($stage === 'ready') {
                continue;
            }

            // delivered: diserahkan CSSD + dikonfirmasi unit.
            $pickup = $pickups->dispatch(
                staff: $cssd,
                unitId: $unit->id,
                batchIds: $batches->pluck('id')->all(),
                method: DeliveryMethod::UnitPickup,
            );
            $pickups->confirmReceipt($pickup, $nakes);
        }

        $this->seedIbsUsageDemo($cssd, $transitions);

        $this->command?->info('Data demo tambahan (DemoBulkSeeder) berhasil dibuat.');
    }

    /**
     * Isi khusus untuk unit IBS: beberapa alat ditandai "sedang dipakai" dan
     * beberapa dibiarkan "belum dipakai" — supaya menu Pendataan Alat di Unit
     * dan checklist "Rincian Alat yang Dikirim" langsung ada isinya untuk dicoba.
     */
    private function seedIbsUsageDemo(User $cssd, ItemBatchTransitionService $transitions): void
    {
        $ibs = Unit::where('code', 'IBS')->firstOrFail();
        $nakes = User::where('unit_id', $ibs->id)->where('role', UserRole::Nakes)->first();

        if (! $nakes) {
            return;
        }

        $order = app(DeliveryOrderService::class)->create($nakes, [
            'courier_name' => 'Demo Pendataan Alat',
            'sent_at' => now()->subHours(6),
            'box_count' => 2,
        ]);

        app(DeliveryOrderService::class)->recordIntake($order, $cssd, [
            ['line_type' => 'set', 'instrument_set_id' => InstrumentSet::where('code', 'SET-MINOR')->value('id'), 'item_id' => null, 'quantity' => 1],
            ['line_type' => 'set', 'instrument_set_id' => InstrumentSet::where('code', 'SET-HECTING')->value('id'), 'item_id' => null, 'quantity' => 1],
            ['line_type' => 'individual', 'instrument_set_id' => null, 'item_id' => Item::where('code', 'ALT-011')->value('id'), 'quantity' => 3],
            ['line_type' => 'individual', 'instrument_set_id' => null, 'item_id' => Item::where('code', 'ALT-013')->value('id'), 'quantity' => 2],
        ]);

        $order->refresh();
        $batches = $order->itemBatches;

        foreach (self::STATIONS as $station) {
            foreach ($batches as $batch) {
                $transitions->transition($batch, $station->targetStatus(), $cssd, ScanInputMethod::HidScanner, $station->label());
            }
        }

        $pickup = app(PickupService::class)->dispatch(
            staff: $cssd,
            unitId: $ibs->id,
            batchIds: $batches->pluck('id')->all(),
            method: DeliveryMethod::UnitPickup,
        );
        app(PickupService::class)->confirmReceipt($pickup, $nakes);

        // Tandai sebagian dipakai (in_use) supaya "Alat Kotor" & checklist order ada isinya,
        // sisanya dibiarkan "belum dipakai" (picked_up) supaya Pendataan Alat di Unit juga ada isinya.
        foreach ($batches as $index => $batch) {
            if ($index % 2 !== 0) {
                continue;
            }

            if ($batch->batch_type === \App\Enums\BatchType::Set) {
                $batch->load('instrumentSet.items');
                foreach ($batch->instrumentSet->items as $setItem) {
                    ItemBatchUsageMark::updateOrCreate(
                        ['item_batch_id' => $batch->id, 'item_id' => $setItem->id],
                        ['is_used' => true, 'marked_by_user_id' => $nakes->id, 'marked_at' => now()],
                    );
                }
            }

            $transitions->transition($batch, ItemBatchStatus::InUse, $nakes, ScanInputMethod::HidScanner, 'Demo — Pendataan Alat di Unit');
        }
    }

    /** @return array<int, array{line_type: string, instrument_set_id: ?int, item_id: ?int, quantity: int, notes: ?string}> */
    private function randomLines(): array
    {
        $lines = [];

        // 1-2 baris set.
        foreach (collect(self::SET_CODES)->random(random_int(1, 2)) as $code) {
            $lines[] = [
                'line_type' => 'set',
                'instrument_set_id' => InstrumentSet::where('code', $code)->value('id'),
                'item_id' => null,
                'quantity' => random_int(1, 3),
                'notes' => null,
            ];
        }

        // 1-3 baris barang lepasan.
        foreach ((array) collect(self::ITEM_CODES)->random(random_int(1, 3)) as $code) {
            $lines[] = [
                'line_type' => 'individual',
                'instrument_set_id' => null,
                'item_id' => Item::where('code', $code)->value('id'),
                'quantity' => random_int(1, 4),
                'notes' => null,
            ];
        }

        return $lines;
    }
}
