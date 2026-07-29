<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Order;
use App\Models\ReturnShipment;
use App\Models\SterilizationRecord;
use App\Models\User;
use App\Services\SterilizationService;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\InventorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Memastikan setiap halaman benar-benar render dengan data nyata di dalamnya,
 * bukan sekadar membalas 200 pada database kosong.
 *
 * Memakai DemoFlowSeeder supaya semua tahap alur terisi (pesanan menunggu,
 * kiriman menggantung, alat dicuci, alat disterilkan, dan seterusnya).
 */
class PageRenderTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $cssd;

    private User $nakes;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->seed(InventorySeeder::class);

        $this->admin = User::firstWhere('email', 'admin@rskemenkes.test');
        $this->cssd = User::firstWhere('email', 'cssd1@rskemenkes.test');
        $this->nakes = User::firstWhere('email', 'ibs@rskemenkes.test');

        $this->buildScenarios();
    }

    /**
     * Membangun contoh transaksi di berbagai tahap.
     *
     * Sengaja dibuat di dalam test, bukan di seeder, karena seeder produksi
     * memang tidak boleh membuat pesanan atau pengembalian palsu.
     */
    private function buildScenarios(): void
    {
        $orders = app(\App\Services\OrderService::class);
        $returns = app(\App\Services\ReturnService::class);
        $registration = app(\App\Services\AssetRegistrationService::class);
        $codes = app(\App\Services\PublicCodeGenerator::class);
        $sterilization = app(SterilizationService::class);
        $transitions = app(\App\Services\AssetTransitionService::class);

        // Bawa beberapa alat ke unit dulu, seperti kondisi sebelum dikirim untuk dicuci.
        $atUnit = Asset::where('status', \App\Enums\AssetStatus::Available)->limit(4)->get();

        foreach ($atUnit as $asset) {
            foreach ([\App\Enums\AssetStatus::Reserved, \App\Enums\AssetStatus::ReadyForHandover,
                \App\Enums\AssetStatus::AtUnit] as $status) {
                $transitions->transition($asset, $status, $this->cssd);
            }
        }

        // 1. Pesanan CITO yang alatnya belum diterima CSSD.
        $orders->create(
            requester: $this->nakes,
            photoPaths: ['order-photos/uji-cito.jpg'],
            notes: 'Butuh segera untuk operasi darurat.',
            isCito: true,
            neededAt: now()->addHours(2)->toDateTimeString(),
        );

        // 2. Pesanan yang sudah diterima CSSD dan alatnya sedang diproses.
        $flowing = $orders->create(
            requester: $this->nakes,
            photoPaths: ['order-photos/uji-alur.jpg'],
        );

        $orders->receiveDirtyAsset($flowing, $atUnit[0], $this->cssd);
        $orders->receiveDirtyAsset($flowing, $atUnit[1], $this->cssd);

        // Satu alat ditinggal di pencucian agar antrian barcode baru terisi.
        $transitions->transition($atUnit[0]->refresh(), \App\Enums\AssetStatus::Washing, $this->cssd);

        // Satu alat lagi didorong sampai tahap sterilisasi.
        $transitions->transition($atUnit[1]->refresh(), \App\Enums\AssetStatus::Washing, $this->cssd);
        $registration->issueNewCode($atUnit[1]->refresh(), $codes->generate(), $this->cssd);
        $sterilization->startSterilizing($atUnit[1]->refresh(), $this->cssd, \App\Enums\SterilizationMethod::Steam);

        // 3. Kiriman alat kotor di luar pesanan, dibiarkan menunggu konfirmasi CSSD.
        $returns->send($this->nakes, [$atUnit[2]->id], reorderBatch: false);
    }

    #[Test]
    public function skenario_uji_mengisi_setiap_tahap_alur(): void
    {
        $this->assertGreaterThan(0, Order::awaitingPreparation()->count(), 'Harus ada pesanan menunggu diterima.');
        $this->assertGreaterThan(0, ReturnShipment::pending()->count(), 'Harus ada kiriman menunggu konfirmasi.');
        $this->assertGreaterThan(0, Asset::where('status', \App\Enums\AssetStatus::Washing)->count());
        $this->assertGreaterThan(0, Asset::where('status', \App\Enums\AssetStatus::Sterilizing)->count());
        $this->assertGreaterThan(0, Asset::inStock()->count());
        $this->assertGreaterThan(0, Order::where('is_cito', true)->count(), 'Harus ada pesanan CITO.');
    }

    #[Test]
    public function halaman_panduan_menampilkan_langkah_sesuai_peran(): void
    {
        $this->actingAs($this->nakes)->get('/panduan')->assertOk()
            ->assertSee('Panduan Penggunaan')->assertSee('Buat pesanan cuci');

        $this->actingAs($this->cssd)->get('/panduan')->assertOk()
            ->assertSee('Pasang barcode baru setelah dekontaminasi');

        $this->actingAs($this->admin)->get('/panduan')->assertOk()
            ->assertSee('Siapkan master data');
    }

    #[Test]
    public function seluruh_halaman_cssd_render_dengan_data(): void
    {
        $order = Order::awaitingPreparation()->firstOrFail();

        $checks = [
            ['/cssd', 'Posisi Alat per Tahap'],
            ['/cssd/order', $order->order_number],
            ['/cssd/order/'.$order->id, 'Terima Alat Kotor'],
            ['/cssd/kiriman', 'Kiriman Alat Kotor'],
            ['/cssd/scan', 'Stasiun Scan'],
            ['/cssd/barcode-baru', 'Antrian Pencucian'],
            ['/cssd/barcode', 'Barcode Kosong'],
            ['/cssd/stok', 'Gudang Steril'],
            ['/cssd/batch', 'Kelola Batch'],
            ['/cssd/laporan', 'Laporan Proses Sterilisasi'],
        ];

        foreach ($checks as [$path, $needle]) {
            $this->actingAs($this->cssd)
                ->get($path)
                ->assertOk()
                ->assertSee($needle, escape: false);
        }
    }

    #[Test]
    public function seluruh_halaman_unit_render_dengan_data(): void
    {
        $order = Order::forUnit($this->nakes->unit_id)->firstOrFail();

        $checks = [
            ['/unit', 'Alat Sedang Diproses'],
            ['/unit/progress', 'Progress Pencucian'],
            ['/unit/order', $order->order_number],
            ['/unit/order/buat', 'Ambil dari Kamera'],
            ['/unit/order/'.$order->id, 'Progres Pesanan'],
            ['/unit/pemakaian', 'Scan Pemakaian Alat'],
        ];

        foreach ($checks as [$path, $needle]) {
            $this->actingAs($this->nakes)
                ->get($path)
                ->assertOk()
                ->assertSee($needle, escape: false);
        }
    }

    #[Test]
    public function seluruh_halaman_admin_render_dengan_data(): void
    {
        $checks = [
            ['/admin', 'Posisi Aset per Zona'],
            ['/admin/telusur', 'Telusur Alat'],
            ['/admin/set-tidak-lengkap', 'Set Tidak Lengkap'],
            ['/admin/unit', 'Instalasi Bedah Sentral'],
            ['/admin/alat', 'Gunting Mayo 17 cm'],
            ['/admin/set-alat', 'Set Laparotomi'],
            ['/admin/pengguna', 'cssd1@rskemenkes.test'],
        ];

        foreach ($checks as [$path, $needle]) {
            $this->actingAs($this->admin)
                ->get($path)
                ->assertOk()
                ->assertSee($needle, escape: false);
        }
    }

    #[Test]
    public function halaman_detail_aset_menampilkan_jejak_audit_dan_riwayat_barcode(): void
    {
        // Aset yang sudah pernah ganti barcode, supaya panel riwayat barcode ikut terisi.
        $asset = Asset::has('codes', '>', 1)->firstOrFail();

        $this->actingAs($this->cssd)
            ->get("/aset/{$asset->id}")
            ->assertOk()
            ->assertSee($asset->current_code)
            ->assertSee('Riwayat Perpindahan')
            ->assertSee('Riwayat Barcode')
            ->assertSee($this->cssd->name);
    }

    #[Test]
    public function halaman_cetak_barcode_menghasilkan_qr_svg(): void
    {
        $asset = Asset::firstOrFail();

        $response = $this->actingAs($this->cssd)
            ->get('/cssd/barcode?q='.$asset->current_code)
            ->assertOk()
            ->assertSee($asset->current_code);

        $this->assertStringContainsString('<svg', $response->getContent());
    }

    #[Test]
    public function laporan_sterilisasi_bisa_dicetak_sebagai_pdf(): void
    {
        $assets = Asset::where('status', \App\Enums\AssetStatus::Sterilizing)->pluck('id')->all();
        $this->assertNotEmpty($assets, 'Butuh alat pada tahap sterilisasi untuk membuat laporan.');

        $record = app(SterilizationService::class)->createRecord(
            staff: $this->cssd,
            method: \App\Enums\SterilizationMethod::Steam,
            assetIds: $assets,
        );

        $response = $this->actingAs($this->cssd)
            ->get("/cssd/laporan/{$record->id}/pdf")
            ->assertOk();

        $this->assertStringContainsString('application/pdf', $response->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    #[Test]
    public function pdf_laporan_merekonstruksi_tahapan_dari_jejak_audit(): void
    {
        $assets = Asset::where('status', \App\Enums\AssetStatus::Sterilizing)->pluck('id')->all();

        $record = app(SterilizationService::class)->createRecord(
            staff: $this->cssd,
            method: \App\Enums\SterilizationMethod::Steam,
            assetIds: $assets,
        );

        $controller = new \App\Livewire\Cssd\ReportPdfController;
        $method = new \ReflectionMethod($controller, 'reconstructStages');
        $stages = $method->invoke($controller, SterilizationRecord::find($record->id)->load('assets'));

        $this->assertNotEmpty($stages, 'Tahapan harus terekonstruksi dari jejak audit.');

        foreach ($stages as $stage) {
            $this->assertNotEmpty($stage['staff'], 'Tiap tahap wajib mencantumkan petugas.');
            $this->assertNotNull($stage['started_at'], 'Tiap tahap wajib punya jam mulai.');
        }
    }

    #[Test]
    public function pusat_notifikasi_menampilkan_notifikasi(): void
    {
        $this->actingAs($this->nakes)
            ->get('/notifikasi')
            ->assertOk()
            ->assertSee('Notifikasi');
    }

    #[Test]
    public function nakes_tidak_bisa_membuka_aset_milik_batch_unit_lain(): void
    {
        $foreign = Asset::query()
            ->whereHas('batch', fn ($b) => $b->where('unit_id', '!=', $this->nakes->unit_id))
            ->first();

        if ($foreign) {
            $this->actingAs($this->nakes)->get("/aset/{$foreign->id}")->assertForbidden();
        }

        // Stok bebas sengaja terbuka supaya unit tahu apa yang bisa dipesan.
        $shared = Asset::inStock()->firstOrFail();
        $this->actingAs($this->nakes)->get("/aset/{$shared->id}")->assertOk();
    }
}
