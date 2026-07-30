<?php

namespace Database\Seeders;

use App\Models\PickupLocation;
use App\Models\Unit;
use Illuminate\Database\Seeder;

/**
 * Contoh awal (dummy) daftar ruangan per unit — Admin bisa ubah/tambah lewat
 * menu Master Data > Lokasi Pengambilan kapan saja setelahnya.
 *
 * Aman dijalankan sendiri di server produksi (`db:seed --class=PickupLocationSeeder`)
 * karena hanya menyentuh tabel pickup_locations — tidak menyentuh Users/Items/dll,
 * jadi tidak ada risiko password akun asli tertimpa ulang seperti seeder utama.
 */
class PickupLocationSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            'IBS' => ['Ruang Operasi 1', 'Ruang Operasi 2', 'Ruang Operasi 3', 'Ruang Operasi 4', 'Ruang Operasi 5', 'Ruang Recovery', 'Ruang Persiapan Operasi'],
            'IGD' => ['Triase', 'Ruang Tindakan 1', 'Ruang Tindakan 2', 'Ruang Resusitasi', 'Ruang Observasi'],
            'ICU' => ['Bed ICU 1', 'Bed ICU 2', 'Bed ICU 3', 'Bed ICU 4', 'Bed ICU 5', 'Nurse Station ICU'],
            'RNP-A' => ['Kamar 101', 'Kamar 102', 'Kamar 103', 'Kamar 104', 'Nurse Station Rawat Inap A'],
            'POLI-GIGI' => ['Ruang Periksa 1', 'Ruang Periksa 2'],
        ];

        foreach ($rows as $unitCode => $names) {
            $unit = Unit::where('code', $unitCode)->first();

            if (! $unit) {
                continue;
            }

            foreach ($names as $name) {
                PickupLocation::updateOrCreate(
                    ['unit_id' => $unit->id, 'name' => $name],
                    ['is_active' => true],
                );
            }
        }
    }
}
