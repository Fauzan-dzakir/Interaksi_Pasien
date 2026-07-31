<?php

namespace Database\Seeders;

use App\Enums\BatchQcStage;
use App\Enums\DeliveryMethod;
use App\Enums\ItemBatchStatus;
use App\Enums\MaterialSensitivity;
use App\Enums\ScanInputMethod;
use App\Enums\ScanStation;
use App\Enums\SterilizationMethod;
use App\Enums\UnitType;
use App\Enums\UserRole;
use App\Models\InstrumentSet;
use App\Models\Item;
use App\Models\ItemBatch;
use App\Models\ItemBatchStageCheck;
use App\Models\ItemBatchUsageMark;
use App\Models\Unit;
use App\Models\User;
use App\Services\DeliveryOrderService;
use App\Services\ItemBatchTransitionService;
use App\Services\PickupService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;

class FinalSimulationSeeder extends Seeder
{
    private const DEMO_PASSWORD = 'password';

    public function run(
        DeliveryOrderService $orders,
        ItemBatchTransitionService $transitions,
        PickupService $pickups,
    ): void {
        // 1. Truncate tables securely
        //
        // Daftar aslinya kurang beberapa tabel (nama pivot instrument_set_item juga salah
        // eja — seharusnya instrument_set_items), sehingga baris "riwayat" lama (event alat,
        // event order, checklist, tanda pemakaian set, deklarasi order, dll) ketinggalan dan
        // jadi sampah yatim piatu menunjuk ke data yang sudah dihapus. Dilengkapi supaya
        // "buang semua data" ini benar-benar bersih total, bukan cuma sebagian.
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('item_batch_stage_checks')->truncate();
        DB::table('item_batch_usage_marks')->truncate();
        DB::table('delivery_order_declared_batches')->truncate();
        DB::table('item_batch_supersessions')->truncate();
        DB::table('item_batch_events')->truncate();
        DB::table('delivery_order_events')->truncate();
        DB::table('pickup_items')->truncate();
        DB::table('pickup_location_requests')->truncate();
        DB::table('instrument_set_items')->truncate();
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
            $this->seedSimulatedData($units, $items, $orders, $transitions, $pickups);
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

            $sensitivity = MaterialSensitivity::tryFrom($row['material_sensitivity'] ?? 'heat_water_resistant') ?? MaterialSensitivity::HeatWaterResistant;

            $items[$code] = Item::create([
                'code' => $code,
                'name' => $row['name'],
                'category' => $row['category'] ?? 'Lainnya',
                'material_sensitivity' => $sensitivity,
                // Metode sterilisasi default mengikuti sensitivitas bahan — alat sensitif
                // panas & air WAJIB EO Gas (satu-satunya yang aman untuknya), alat sensitif
                // panas tapi tahan air pakai Plasma H2O2, sisanya (mayoritas alat bedah
                // standar) pakai Autoclave. Ini yang menentukan masa kedaluwarsa steril
                // otomatis (Autoclave & Plasma H2O2: 6 bulan, Gas EO: 12 bulan).
                'sterilization_method' => match ($sensitivity) {
                    MaterialSensitivity::HeatSensitiveWaterSensitive => SterilizationMethod::Gas,
                    MaterialSensitivity::HeatSensitiveWaterResistant => SterilizationMethod::Plasma,
                    MaterialSensitivity::HeatWaterResistant => SterilizationMethod::Autoclave,
                },
                'is_active' => true,
            ]);
        }
        return $items;
    }

    private function seedInstrumentSets(array $items): void
    {
        $jsonPath = database_path('data/real_sets.json');
        if (!File::exists($jsonPath)) {
            $this->command->warn("File $jsonPath tidak ditemukan. Melewati pembuatan Set otentik.");
            return;
        }

        // Kode di file JSON ini hasil pemotongan otomatis dari nama set (ada batas
        // 32 karakter di kolom `code`) — beberapa nama berbeda kebetulan terpotong
        // jadi kode yang SAMA persis (mis. dua set "Checlist Surgical Instrument
        // Set ..." yang beda akhiran). Dedup di sini supaya tidak gagal karena
        // constraint unique, tanpa perlu mengubah file sumber datanya.
        $usedCodes = [];

        $setsData = json_decode(File::get($jsonPath), true);
        foreach ($setsData as $setRow) {
            $code = $setRow['code'];
            $suffix = 2;
            while (isset($usedCodes[$code])) {
                $code = substr($setRow['code'], 0, 32 - strlen('-'.$suffix)) . '-' . $suffix;
                $suffix++;
            }
            $usedCodes[$code] = true;

            $set = InstrumentSet::create([
                'code' => $code,
                'name' => $setRow['name'],
                'description' => $setRow['description'] ?? '',
                'is_active' => true,
            ]);

            $syncData = [];
            foreach ($setRow['items'] as $itemData) {
                $itemCode = $itemData['code'];
                $qty = $itemData['qty'];

                // Cek apakah item benar-benar ada di database (katalog)
                if (isset($items[$itemCode])) {
                    $syncData[$items[$itemCode]->id] = ['quantity' => $qty];
                }
            }
            $set->items()->sync($syncData);
        }
    }

    /**
     * Bikin order/alat/pickup sungguhan pakai katalog & set REAL yang baru
     * diseed, tersebar di seluruh status/zona sistem — supaya tiap menu
     * (Dashboard, Order Masuk, Pendataan Alat di Unit, Distribusi, dst)
     * langsung ada isinya untuk dicoba, bukan cuma katalog kosong.
     *
     * @param  array<string, Unit>  $units
     * @param  array<string, Item>  $items
     */
    private function seedSimulatedData(
        array $units,
        array $items,
        DeliveryOrderService $orders,
        ItemBatchTransitionService $transitions,
        PickupService $pickups,
    ): void {
        $cssd = User::where('role', UserRole::CssdStaff)->first();

        if (! $cssd) {
            $this->command?->warn('Tidak ada user CSSD, lewati simulasi transaksi.');

            return;
        }

        $itemCodes = array_keys($items);
        $setCodes = InstrumentSet::pluck('code')->all();

        if ($itemCodes === [] || $setCodes === []) {
            $this->command?->warn('Katalog alat/set kosong, lewati simulasi transaksi.');

            return;
        }

        $couriers = ['Budi Santoso', 'Sri Wahyuni', 'Agus Setiawan', 'Dewi Anggraini', 'Rudi Hartono'];

        // unit, stage, cito, stopAt (khusus 'processing' — null = acak),
        // expireSterile (tandai sebagian alat sudah lewat masa steril, buat
        // isi kartu "Kedaluwarsa Steril" di dashboard CSSD).
        //
        // stopAt 1 & 2 sengaja dipakai berulang di beberapa order berbeda —
        // supaya beberapa order alatnya "menumpuk" di tahap yang sama (mis.
        // Proses Pencucian), pas buat contoh tombol pemindahan massal lintas
        // order di Dashboard CSSD.
        $recipes = [
            ['unit' => 'IBS', 'stage' => 'processing', 'cito' => false, 'stopAt' => 1],
            ['unit' => 'IBS', 'stage' => 'processing', 'cito' => false, 'stopAt' => 1],
            ['unit' => 'IBS', 'stage' => 'processing', 'cito' => true, 'stopAt' => 2],
            ['unit' => 'IBS', 'stage' => 'processing', 'cito' => false, 'stopAt' => 6],
            ['unit' => 'IBS', 'stage' => 'ready', 'cito' => false],
            ['unit' => 'IBS', 'stage' => 'ready', 'cito' => false, 'expireSterile' => true],
            ['unit' => 'IBS', 'stage' => 'delivered', 'cito' => false],
            ['unit' => 'IBS', 'stage' => 'delivered', 'cito' => false],
            ['unit' => 'IBS', 'stage' => 'pending', 'cito' => false],
            ['unit' => 'IGD', 'stage' => 'processing', 'cito' => true, 'stopAt' => 3],
            ['unit' => 'IGD', 'stage' => 'processing', 'cito' => false, 'stopAt' => 1],
            ['unit' => 'IGD', 'stage' => 'ready', 'cito' => false],
            ['unit' => 'IGD', 'stage' => 'delivered', 'cito' => false],
            ['unit' => 'IGD', 'stage' => 'pending', 'cito' => false],
            ['unit' => 'IGD', 'stage' => 'lost', 'cito' => false],
            ['unit' => 'ICU', 'stage' => 'processing', 'cito' => false, 'stopAt' => 2],
            ['unit' => 'ICU', 'stage' => 'processing', 'cito' => false],
            ['unit' => 'ICU', 'stage' => 'ready', 'cito' => false],
            ['unit' => 'ICU', 'stage' => 'delivered', 'cito' => false],
            ['unit' => 'ICU', 'stage' => 'pending', 'cito' => true],
            ['unit' => 'ICU', 'stage' => 'retired', 'cito' => false],
            ['unit' => 'RNP-A', 'stage' => 'processing', 'cito' => false],
            ['unit' => 'RNP-A', 'stage' => 'processing', 'cito' => false, 'stopAt' => 6],
            ['unit' => 'RNP-A', 'stage' => 'ready', 'cito' => false],
            ['unit' => 'RNP-A', 'stage' => 'delivered', 'cito' => false],
            ['unit' => 'RNP-A', 'stage' => 'pending', 'cito' => false],
            ['unit' => 'POLI-GIGI', 'stage' => 'processing', 'cito' => false],
            ['unit' => 'POLI-GIGI', 'stage' => 'delivered', 'cito' => false],
        ];

        $stations = $this->cssdStations();

        foreach ($recipes as $index => $recipe) {
            $unit = $units[$recipe['unit']] ?? null;

            if (! $unit) {
                continue;
            }

            $nakes = User::where('unit_id', $unit->id)->where('role', UserRole::Nakes)->first();

            if (! $nakes) {
                continue;
            }

            $isCito = $recipe['cito'] ?? false;

            $order = $orders->create($nakes, [
                'courier_name' => $couriers[$index % count($couriers)],
                'sent_at' => now()->subHours(random_int(1, 96)),
                'box_count' => random_int(1, 4),
                'is_cito' => $isCito,
                'needed_at' => $isCito ? now()->addHours(random_int(1, 6)) : null,
                'notes' => $isCito ? 'Mohon didahulukan, jadwal operasi mendesak.' : null,
            ]);

            if ($recipe['stage'] === 'pending') {
                continue;
            }

            $orders->recordIntake($order, $cssd, $this->randomLines($itemCodes, $items, $setCodes));
            $order->refresh();
            $batches = $order->itemBatches;

            if ($recipe['stage'] === 'processing') {
                $stopAt = $recipe['stopAt'] ?? random_int(1, 7);

                foreach (array_slice($stations, 0, $stopAt) as $station) {
                    foreach ($batches as $batch) {
                        $transitions->transition($batch, $station->targetStatus(), $cssd, ScanInputMethod::HidScanner, $station->label());
                    }
                }

                $orders->syncStatus($order->fresh(), $cssd);

                continue;
            }

            if ($recipe['stage'] === 'lost' || $recipe['stage'] === 'retired') {
                foreach ($stations as $station) {
                    foreach ($batches as $batch) {
                        $transitions->transition($batch, $station->targetStatus(), $cssd, ScanInputMethod::HidScanner, $station->label());
                    }
                }

                $target = $recipe['stage'] === 'lost' ? ItemBatchStatus::Lost : ItemBatchStatus::Retired;
                $transitions->adminOverride(
                    $batches->first(),
                    $target,
                    $cssd,
                    $recipe['stage'] === 'lost' ? 'Tidak ditemukan saat stock opname rutin.' : 'Rusak permanen, tidak layak pakai lagi.',
                );

                $orders->syncStatus($order->fresh(), $cssd);

                continue;
            }

            // ready / delivered — semua batch diproses penuh sampai gudang steril.
            foreach ($stations as $station) {
                foreach ($batches as $batch) {
                    $transitions->transition($batch, $station->targetStatus(), $cssd, ScanInputMethod::HidScanner, $station->label());
                }
            }
            $orders->syncStatus($order->fresh(), $cssd);

            if ($recipe['expireSterile'] ?? false) {
                foreach ($batches as $batch) {
                    $batch->forceFill(['sterilization_expired_at' => now()->subDays(random_int(5, 40))])->save();
                }
            }

            if ($recipe['stage'] === 'ready') {
                continue;
            }

            // delivered: IBS DIANTAR CSSD (mirip alur jemput alat kotor ke IBS),
            // unit lain AMBIL SENDIRI — asimetri yang sama seperti arah kotor.
            $method = $unit->type === UnitType::IbsOk ? DeliveryMethod::DirectDelivery : DeliveryMethod::UnitPickup;
            $pickup = $pickups->dispatch(staff: $cssd, unitId: $unit->id, batchIds: $batches->pluck('id')->all(), method: $method);
            $pickups->confirmReceipt($pickup, $nakes);
        }

        // Pendataan Alat di Unit — IBS dan satu unit non-IBS (ICU), supaya
        // checklist per-set (dan tombol hijau "Konfirmasi Pemakaian") langsung
        // ada contohnya di kedua jenis unit, bukan cuma IBS.
        $this->seedUnitInventoryDemo($units['IBS'], $cssd, $orders, $transitions, $pickups, $itemCodes, $items, $setCodes);
        $this->seedUnitInventoryDemo($units['ICU'], $cssd, $orders, $transitions, $pickups, $itemCodes, $items, $setCodes);

        // Beberapa contoh riwayat Checklist QC, supaya halaman detail alat
        // langsung ada isinya untuk dilihat (checklist opsional, jadi tanpa ini
        // riwayatnya kosong semua — wajar tapi kurang meyakinkan buat demo).
        $this->seedQcHistorySample($cssd);

        $this->command?->info('Simulasi data transaksi (order, alat, pickup, pemakaian) berhasil dibuat dari katalog real.');
    }

    /** @return array<int, ScanStation> Urutan stasiun CSSD dari kotor sampai gudang steril. */
    private function cssdStations(): array
    {
        return [
            ScanStation::Washing, ScanStation::Drying, ScanStation::CleanlinessCheck,
            ScanStation::CleanlinessPass, ScanStation::Packaging, ScanStation::Sterilizing,
            ScanStation::SterileCheck, ScanStation::StoragePass,
        ];
    }

    /**
     * @param  array<int, string>  $itemCodes
     * @param  array<string, Item>  $items
     * @param  array<int, string>  $setCodes
     * @return array<int, array{line_type: string, instrument_set_id: ?int, item_id: ?int, quantity: int, notes: ?string}>
     */
    private function randomLines(array $itemCodes, array $items, array $setCodes): array
    {
        $lines = [];

        foreach (collect($setCodes)->random(min(count($setCodes), random_int(1, 2))) as $code) {
            $lines[] = [
                'line_type' => 'set',
                'instrument_set_id' => InstrumentSet::where('code', $code)->value('id'),
                'item_id' => null,
                'quantity' => random_int(1, 2),
                'notes' => null,
            ];
        }

        foreach (collect($itemCodes)->random(min(count($itemCodes), random_int(2, 4))) as $code) {
            $lines[] = [
                'line_type' => 'individual',
                'instrument_set_id' => null,
                'item_id' => $items[$code]->id,
                'quantity' => random_int(1, 4),
                'notes' => null,
            ];
        }

        return $lines;
    }

    /**
     * Isi khusus satu unit: sebagian alat ditandai "sedang dipakai" (termasuk
     * lewat checklist per-isi-set), sebagian dibiarkan "belum dipakai" —
     * supaya menu Pendataan Alat di Unit langsung ada isinya buat dicoba.
     *
     * @param  array<int, string>  $itemCodes
     * @param  array<string, Item>  $items
     * @param  array<int, string>  $setCodes
     */
    private function seedUnitInventoryDemo(
        Unit $unit,
        User $cssd,
        DeliveryOrderService $orders,
        ItemBatchTransitionService $transitions,
        PickupService $pickups,
        array $itemCodes,
        array $items,
        array $setCodes,
    ): void {
        $nakes = User::where('unit_id', $unit->id)->where('role', UserRole::Nakes)->first();

        if (! $nakes) {
            return;
        }

        $order = $orders->create($nakes, [
            'courier_name' => 'Demo Pendataan Alat',
            'sent_at' => now()->subHours(6),
            'box_count' => 2,
        ]);

        $orders->recordIntake($order, $cssd, $this->randomLines($itemCodes, $items, $setCodes));
        $order->refresh();
        $batches = $order->itemBatches;

        foreach ($this->cssdStations() as $station) {
            foreach ($batches as $batch) {
                $transitions->transition($batch, $station->targetStatus(), $cssd, ScanInputMethod::HidScanner, $station->label());
            }
        }

        $method = $unit->type === UnitType::IbsOk ? DeliveryMethod::DirectDelivery : DeliveryMethod::UnitPickup;
        $pickup = $pickups->dispatch(staff: $cssd, unitId: $unit->id, batchIds: $batches->pluck('id')->all(), method: $method);
        $pickups->confirmReceipt($pickup, $nakes);

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

    /** Contoh riwayat Checklist QC (Dekontaminasi/Steril) pada beberapa alat yang sudah lewat tahap itu. */
    private function seedQcHistorySample(User $cssd): void
    {
        $sampleBatches = ItemBatch::whereIn('status', [
            ItemBatchStatus::InStorage, ItemBatchStatus::ReadyForPickup,
            ItemBatchStatus::PickedUp, ItemBatchStatus::InUse,
        ])->inRandomOrder()->limit(6)->get();

        foreach ($sampleBatches as $i => $batch) {
            $stage = $i % 2 === 0 ? BatchQcStage::Dekontaminasi : BatchQcStage::Steril;

            foreach ($stage->items() as $item) {
                ItemBatchStageCheck::create([
                    'item_batch_id' => $batch->id,
                    'stage' => $stage->value,
                    'item_key' => $item['key'],
                    'item_label' => $item['label'],
                    'is_present' => true,
                    'note' => null,
                    'recorded_by_user_id' => $cssd->id,
                    'recorded_at' => $batch->status_changed_at ?? now(),
                ]);
            }
        }
    }
}
