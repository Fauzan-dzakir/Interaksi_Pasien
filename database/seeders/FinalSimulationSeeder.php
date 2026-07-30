<?php

namespace Database\Seeders;

use App\Enums\MaterialSensitivity;
use App\Enums\UnitType;
use App\Enums\UserRole;
use App\Models\InstrumentSet;
use App\Models\Item;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;

class FinalSimulationSeeder extends Seeder
{
    private const DEMO_PASSWORD = 'password';

    public function run(): void
    {
        // 1. Truncate tables securely
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('instrument_set_item')->truncate();
        DB::table('delivery_order_line_item_checks')->truncate();
        DB::table('delivery_order_lines')->truncate();
        DB::table('delivery_orders')->truncate();
        DB::table('item_batches')->truncate();
        DB::table('pickups')->truncate();
        DB::table('pickup_locations')->truncate();
        DB::table('instrument_sets')->truncate();
        DB::table('items')->truncate();
        DB::table('users')->truncate();
        DB::table('units')->truncate();
        DB::table('notifications')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $units = $this->seedUnits();
        $this->seedUsers($units);
        $this->call(PickupLocationSeeder::class);
        $items = $this->seedItemsFromJson();
        
        if (count($items) > 0) {
            $this->seedInstrumentSets($items);
            $this->seedSimulatedData($units, $items);
        }
    }

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
            $units[$row['code']] = Unit::create(
                ['code' => $row['code'], 'name' => $row['name'], 'type' => $row['type'], 'is_active' => true]
            );
        }
        return $units;
    }

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
            User::create([
                'name' => $row['name'],
                'email' => $row['email'],
                'role' => $row['role'],
                'unit_id' => $row['unit'] ? $units[$row['unit']]->id : null,
                'is_active' => true,
                'password' => Hash::make(self::DEMO_PASSWORD),
                'email_verified_at' => now(),
            ]);
        }
    }

    private function seedItemsFromJson(): array
    {
        $jsonPath = database_path('data/items.json');
        if (!File::exists($jsonPath)) {
            $this->command->warn("File $jsonPath tidak ditemukan. Melewati seeding items.");
            return [];
        }

        $itemsData = json_decode(File::get($jsonPath), true);
        $items = [];
        foreach ($itemsData as $row) {
            $code = $row['code'] ?? null;
            if (!$code) continue;

            $items[$code] = Item::create([
                'code' => $code,
                'name' => $row['name'],
                'category' => $row['category'] ?? 'Lainnya',
                'material_sensitivity' => MaterialSensitivity::tryFrom($row['material_sensitivity'] ?? 'heat_water_resistant') ?? MaterialSensitivity::HeatWaterResistant,
                'is_active' => true,
            ]);
        }
        return $items;
    }

    private function seedInstrumentSets(array $items): void
    {
        // Ambil beberapa item acak untuk membuat set
        $itemKeys = array_keys($items);
        
        $sets = [
            [
                'code' => 'SET-MAYOR-01',
                'name' => 'Set Bedah Mayor',
                'description' => 'Set standar untuk bedah mayor / laparotomi.',
                'item_count' => 15
            ],
            [
                'code' => 'SET-MINOR-01',
                'name' => 'Set Bedah Minor',
                'description' => 'Set standar untuk tindakan bedah minor di IGD/Poli.',
                'item_count' => 8
            ],
            [
                'code' => 'SET-HECTING',
                'name' => 'Set Jahit Luka (Hecting)',
                'description' => 'Set lengkap untuk jahit luka.',
                'item_count' => 5
            ]
        ];

        foreach ($sets as $setRow) {
            $set = InstrumentSet::create([
                'code' => $setRow['code'],
                'name' => $setRow['name'],
                'description' => $setRow['description'],
                'is_active' => true,
            ]);

            // Ambil item acak
            $randomKeys = (array) array_rand(array_flip($itemKeys), $setRow['item_count']);
            $syncData = [];
            foreach ($randomKeys as $key) {
                $qty = rand(1, 4);
                $syncData[$items[$key]->id] = ['quantity' => $qty];
            }
            $set->items()->sync($syncData);
        }
    }

    private function seedSimulatedData($units, $items): void
    {
        // Dalam simulasi nyata, akan ada pembuatan Pickups, ItemBatches, DeliveryOrders.
        // Berhubung kita fokus pada katalog, kita lewati pembuatan transaksi kompleks ini 
        // atau jika sangat diperlukan bisa dipanjangkan. Untuk simulasi yang berfungsi,
        // struktur tabel perlu di-populate menggunakan model Factory jika tersedia.
        $this->command->info("Simulasi data berhasil (Katalog Alat, Unit, User, Set terisi lengkap). Transaksi pesanan bisa dites langsung lewat antarmuka web.");
    }
}
