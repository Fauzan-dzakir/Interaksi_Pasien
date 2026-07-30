<?php

namespace Database\Seeders;

use App\Enums\MaterialSensitivity;
use App\Enums\UnitType;
use App\Enums\UserRole;
use App\Models\InstrumentSet;
use App\Models\Item;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Data awal untuk pengembangan & demo Fase 1a.
 *
 * PERHATIAN: kata sandi di bawah hanya untuk lingkungan lokal/demo.
 * Sebelum dipakai di RS, semua akun wajib ganti kata sandi.
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    private const DEMO_PASSWORD = 'password';

    public function run(): void
    {
        $units = $this->seedUnits();
        $items = $this->seedItems();
        $this->seedInstrumentSets($items);
        $this->seedUsers($units);
        $this->call(PickupLocationSeeder::class);
    }

    /** @return array<string, Unit> */
    private function seedUnits(): array
    {
        $rows = [
            ['code' => 'CSSD', 'name' => 'CSSD', 'type' => UnitType::Cssd],
            ['code' => 'IBS', 'name' => 'Instalasi Bedah Sentral', 'type' => UnitType::IbsOk],
            ['code' => 'IGD', 'name' => 'Instalasi Gawat Darurat', 'type' => UnitType::Other],
            ['code' => 'ICU', 'name' => 'Ruang ICU', 'type' => UnitType::Ward],
            ['code' => 'RNP-A', 'name' => 'Ruang Rawat Inap A', 'type' => UnitType::Ward],
            ['code' => 'POLI-GIGI', 'name' => 'Poli Gigi', 'type' => UnitType::Other],
        ];

        $units = [];

        foreach ($rows as $row) {
            $units[$row['code']] = Unit::updateOrCreate(
                ['code' => $row['code']],
                ['name' => $row['name'], 'type' => $row['type'], 'is_active' => true],
            );
        }

        return $units;
    }

    /** @return array<string, Item> */
    private function seedItems(): array
    {
        $rows = [
            // Tahan panas & tahan uap air -> jalur washer-disinfector + drying suhu tinggi.
            ['code' => 'ALT-001', 'name' => 'Gunting Metzenbaum 18 cm', 'category' => 'Gunting', 'sens' => MaterialSensitivity::HeatWaterResistant],
            ['code' => 'ALT-002', 'name' => 'Gunting Mayo 17 cm', 'category' => 'Gunting', 'sens' => MaterialSensitivity::HeatWaterResistant],
            ['code' => 'ALT-003', 'name' => 'Klem Kocher Lurus 16 cm', 'category' => 'Klem', 'sens' => MaterialSensitivity::HeatWaterResistant],
            ['code' => 'ALT-004', 'name' => 'Klem Pean Bengkok 16 cm', 'category' => 'Klem', 'sens' => MaterialSensitivity::HeatWaterResistant],
            ['code' => 'ALT-005', 'name' => 'Pinset Anatomis 14 cm', 'category' => 'Pinset', 'sens' => MaterialSensitivity::HeatWaterResistant],
            ['code' => 'ALT-006', 'name' => 'Pinset Chirurgis 14 cm', 'category' => 'Pinset', 'sens' => MaterialSensitivity::HeatWaterResistant],
            ['code' => 'ALT-007', 'name' => 'Needle Holder Mayo-Hegar 16 cm', 'category' => 'Needle Holder', 'sens' => MaterialSensitivity::HeatWaterResistant],
            ['code' => 'ALT-008', 'name' => 'Scalpel Handle No. 4', 'category' => 'Scalpel', 'sens' => MaterialSensitivity::HeatWaterResistant],
            ['code' => 'ALT-009', 'name' => 'Retraktor Langenbeck', 'category' => 'Retraktor', 'sens' => MaterialSensitivity::HeatWaterResistant],
            ['code' => 'ALT-010', 'name' => 'Bengkok Stainless', 'category' => 'Wadah', 'sens' => MaterialSensitivity::HeatWaterResistant],

            // Sensitif panas & tahan air -> manual + bilas air RO, drying suhu rendah.
            ['code' => 'ALT-011', 'name' => 'Selang Suction Silikon', 'category' => 'Selang', 'sens' => MaterialSensitivity::HeatSensitiveWaterResistant],
            ['code' => 'ALT-012', 'name' => 'Laringoskop Blade Macintosh', 'category' => 'Anestesi', 'sens' => MaterialSensitivity::HeatSensitiveWaterResistant],
            ['code' => 'ALT-013', 'name' => 'Sungkup Anestesi Silikon', 'category' => 'Anestesi', 'sens' => MaterialSensitivity::HeatSensitiveWaterResistant],

            // Sensitif panas & sensitif air -> usap disinfektan, lap manual, sterilisasi gas.
            ['code' => 'ALT-014', 'name' => 'Kabel Elektrokauter', 'category' => 'Elektromedik', 'sens' => MaterialSensitivity::HeatSensitiveWaterSensitive],
            ['code' => 'ALT-015', 'name' => 'Kamera Endoskopi', 'category' => 'Endoskopi', 'sens' => MaterialSensitivity::HeatSensitiveWaterSensitive],
            ['code' => 'ALT-016', 'name' => 'Light Guide Cable Endoskopi', 'category' => 'Endoskopi', 'sens' => MaterialSensitivity::HeatSensitiveWaterSensitive],
        ];

        $items = [];

        foreach ($rows as $row) {
            $items[$row['code']] = Item::updateOrCreate(
                ['code' => $row['code']],
                [
                    'name' => $row['name'],
                    'category' => $row['category'],
                    'material_sensitivity' => $row['sens'],
                    'is_active' => true,
                ],
            );
        }

        return $items;
    }

    /** @param  array<string, Item>  $items */
    private function seedInstrumentSets(array $items): void
    {
        $sets = [
            [
                'code' => 'SET-MINOR',
                'name' => 'Set Bedah Minor',
                'description' => 'Set standar tindakan bedah minor.',
                'items' => [
                    'ALT-002' => 1, 'ALT-003' => 2, 'ALT-004' => 4,
                    'ALT-005' => 1, 'ALT-006' => 1, 'ALT-007' => 1, 'ALT-008' => 1,
                ],
            ],
            [
                'code' => 'SET-LAPARO',
                'name' => 'Set Laparotomi',
                'description' => 'Set bedah perut mayor.',
                'items' => [
                    'ALT-001' => 2, 'ALT-002' => 1, 'ALT-003' => 6, 'ALT-004' => 8,
                    'ALT-005' => 2, 'ALT-006' => 2, 'ALT-007' => 2, 'ALT-009' => 2, 'ALT-010' => 1,
                ],
            ],
            [
                'code' => 'SET-HECTING',
                'name' => 'Set Hecting',
                'description' => 'Set jahit luka untuk IGD.',
                'items' => [
                    'ALT-002' => 1, 'ALT-004' => 2, 'ALT-006' => 1, 'ALT-007' => 1, 'ALT-010' => 1,
                ],
            ],
        ];

        foreach ($sets as $row) {
            $set = InstrumentSet::updateOrCreate(
                ['code' => $row['code']],
                ['name' => $row['name'], 'description' => $row['description'], 'is_active' => true],
            );

            $set->items()->sync(
                collect($row['items'])
                    ->mapWithKeys(fn (int $qty, string $code) => [$items[$code]->id => ['quantity' => $qty]])
                    ->all()
            );
        }
    }

    /** @param  array<string, Unit>  $units */
    private function seedUsers(array $units): void
    {
        $rows = [
            ['name' => 'Administrator', 'email' => 'admin@rskemenkes.test', 'role' => UserRole::Admin, 'unit' => null],
            ['name' => 'Siti Rahmawati', 'email' => 'cssd1@rskemenkes.test', 'role' => UserRole::CssdStaff, 'unit' => 'CSSD'],
            ['name' => 'Bagus Prasetyo', 'email' => 'cssd2@rskemenkes.test', 'role' => UserRole::CssdStaff, 'unit' => 'CSSD'],
            ['name' => 'dr. Andi Wijaya, Sp.B', 'email' => 'ibs@rskemenkes.test', 'role' => UserRole::Nakes, 'unit' => 'IBS'],
            ['name' => 'Ns. Dewi Lestari', 'email' => 'igd@rskemenkes.test', 'role' => UserRole::Nakes, 'unit' => 'IGD'],
            ['name' => 'Ns. Rizky Ramadhan', 'email' => 'icu@rskemenkes.test', 'role' => UserRole::Nakes, 'unit' => 'ICU'],
        ];

        foreach ($rows as $row) {
            User::updateOrCreate(
                ['email' => $row['email']],
                [
                    'name' => $row['name'],
                    'role' => $row['role'],
                    'unit_id' => $row['unit'] ? $units[$row['unit']]->id : null,
                    'is_active' => true,
                    'password' => Hash::make(self::DEMO_PASSWORD),
                    'email_verified_at' => now(),
                ],
            );
        }
    }
}
