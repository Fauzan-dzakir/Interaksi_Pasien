<?php

namespace Database\Seeders;

use App\Enums\AssetType;
use App\Enums\UserRole;
use App\Models\Batch;
use App\Models\InstrumentSet;
use App\Models\Item;
use App\Models\Unit;
use App\Models\User;
use App\Services\AssetRegistrationService;
use App\Services\DocumentNumberGenerator;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Mendaftarkan inventaris awal: aset fisik beserta barcode-nya, dan batch kosong
 * untuk tiap unit yang memakai alat berulang.
 *
 * Seeder ini SENGAJA tidak membuat pesanan maupun pengembalian contoh, karena
 * sistem akan langsung dipakai untuk data sungguhan. Semua transaksi dimulai
 * dari nol lewat aplikasi.
 *
 * Jalankan: php artisan db:seed --class=InventorySeeder
 */
class InventorySeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(AssetRegistrationService $registration, DocumentNumberGenerator $numbers): void
    {
        $cssd = User::where('role', UserRole::CssdStaff)->firstOrFail();

        $this->seedAssets($registration, $cssd);
        $this->seedBatches($numbers, $cssd);

        $this->command?->info('Inventaris awal berhasil didaftarkan.');
    }

    /** Jumlah awal tiap jenis aset. Sesuaikan dengan stok fisik RS sebelum dijalankan. */
    private function seedAssets(AssetRegistrationService $registration, User $cssd): void
    {
        $setPlan = ['SET-MINOR' => 4, 'SET-LAPARO' => 3, 'SET-HECTING' => 4];

        foreach ($setPlan as $code => $count) {
            $set = InstrumentSet::where('code', $code)->firstOrFail();

            for ($i = 0; $i < $count; $i++) {
                $registration->register(AssetType::Set, $set->id, null, $cssd);
            }
        }

        $itemPlan = [
            'ALT-001' => 6, 'ALT-002' => 8, 'ALT-003' => 10, 'ALT-005' => 8,
            'ALT-007' => 6, 'ALT-010' => 5, 'ALT-011' => 4, 'ALT-013' => 4, 'ALT-015' => 2,
        ];

        foreach ($itemPlan as $code => $count) {
            $item = Item::where('code', $code)->firstOrFail();

            for ($i = 0; $i < $count; $i++) {
                $registration->register(AssetType::Item, null, $item->id, $cssd);
            }
        }
    }

    /**
     * Batch dibuat kosong. Aset masuk ke batch saat unit memesannya, dan keluar
     * lagi kalau unit tidak memesan ulang.
     */
    private function seedBatches(DocumentNumberGenerator $numbers, User $cssd): void
    {
        $plan = [
            'IBS' => 'IBS OK 1',
            'IGD' => 'IGD Tindakan',
            'ICU' => 'ICU Harian',
        ];

        foreach ($plan as $unitCode => $name) {
            $unit = Unit::where('code', $unitCode)->first();

            if (! $unit || Batch::where('unit_id', $unit->id)->where('name', $name)->exists()) {
                continue;
            }

            Batch::create([
                'code' => $numbers->batch(),
                'name' => $name,
                'unit_id' => $unit->id,
                'created_by_user_id' => $cssd->id,
                'is_active' => true,
            ]);
        }
    }
}
