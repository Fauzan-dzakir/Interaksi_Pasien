<?php

namespace Tests\Feature;

use App\Enums\DeliveryMethod;
use App\Enums\ItemBatchStatus;
use App\Enums\ScanStation;
use App\Livewire\Cssd\OrderIntake;
use App\Livewire\Cssd\ScanStation as ScanStationComponent;
use App\Livewire\Unit\OrderCreate;
use App\Models\DeliveryOrder;
use App\Models\InstrumentSet;
use App\Models\Item;
use App\Models\ItemBatch;
use App\Models\Unit;
use App\Models\User;
use App\Services\PickupService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Memastikan setiap halaman benar-benar render dengan data nyata di dalamnya —
 * bukan sekadar balas 200 pada database kosong.
 */
class PageRenderTest extends TestCase
{
    use RefreshDatabase;

    private User $nakes;

    private User $cssd;

    private User $admin;

    private DeliveryOrder $order;

    private ItemBatch $batch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->nakes = User::firstWhere('email', 'ibs@rskemenkes.test');
        $this->cssd = User::firstWhere('email', 'cssd1@rskemenkes.test');
        $this->admin = User::firstWhere('email', 'admin@rskemenkes.test');

        $this->buildLiveData();
    }

    /** Menyiapkan satu order yang sudah didata dan alat yang sudah masuk gudang steril. */
    private function buildLiveData(): void
    {
        Livewire::actingAs($this->nakes)
            ->test(OrderCreate::class)
            ->set('courier_name', 'Budi Santoso')
            ->set('sent_at', now()->format('Y-m-d\TH:i'))
            ->set('box_count', 2)
            ->call('save');

        $this->order = DeliveryOrder::firstWhere('origin_unit_id', Unit::firstWhere('code', 'IBS')->id);

        Livewire::actingAs($this->cssd)
            ->test(OrderIntake::class, ['order' => $this->order])
            ->set('lines', [
                ['line_type' => 'set', 'instrument_set_id' => (string) InstrumentSet::firstWhere('code', 'SET-MINOR')->id, 'item_id' => '', 'quantity' => 1, 'notes' => ''],
                ['line_type' => 'individual', 'instrument_set_id' => '', 'item_id' => (string) Item::firstWhere('code', 'ALT-002')->id, 'quantity' => 3, 'notes' => ''],
            ])
            ->call('save');

        $this->batch = $this->order->itemBatches()->first();

        // Dorong seluruh alat sampai gudang steril agar halaman distribusi ada isinya.
        $stations = [
            ScanStation::Washing, ScanStation::Drying, ScanStation::CleanlinessCheck,
            ScanStation::CleanlinessPass, ScanStation::Packaging, ScanStation::Sterilizing,
            ScanStation::SterileCheck, ScanStation::StoragePass,
        ];

        foreach ($stations as $station) {
            $component = Livewire::actingAs($this->cssd)
                ->test(ScanStationComponent::class)
                ->set('station', $station->value);

            foreach ($this->order->itemBatches as $b) {
                $component->call('handleScan', $b->public_code, 'hid_scanner');
            }
        }

        // Satu serah terima menggantung, supaya kotak masuk penerimaan unit terisi.
        app(PickupService::class)->dispatch(
            staff: $this->cssd,
            unitId: $this->order->origin_unit_id,
            batchIds: [$this->batch->id],
            method: DeliveryMethod::UnitPickup,
        );
    }

    #[Test]
    public function seluruh_halaman_unit_render_dengan_data(): void
    {
        $this->actingAs($this->nakes)->get('/unit')->assertOk()->assertSee('Zona Kotor');
        $this->actingAs($this->nakes)->get('/unit/order')->assertOk()->assertSee($this->order->order_number);
        $this->actingAs($this->nakes)->get('/unit/order/buat')->assertOk()->assertSee('Jumlah Box');
        $this->actingAs($this->nakes)->get("/unit/order/{$this->order->id}")->assertOk()->assertSee('Set Bedah Minor');
        $this->actingAs($this->nakes)->get('/unit/penerimaan')->assertOk()->assertSee('Diterima');
    }

    #[Test]
    public function seluruh_halaman_cssd_render_dengan_data(): void
    {
        $this->actingAs($this->cssd)->get('/cssd')->assertOk()->assertSee('Rincian per Tahap');
        $this->actingAs($this->cssd)->get('/cssd/order')->assertOk()->assertSee($this->order->order_number);
        $this->actingAs($this->cssd)->get("/cssd/order/{$this->order->id}")->assertOk()->assertSee('Hasil Pendataan');
        $this->actingAs($this->cssd)->get('/cssd/scan')->assertOk()->assertSee('Stasiun Scan');
        $this->actingAs($this->cssd)->get('/cssd/ganti-barcode')->assertOk()->assertSee('Ganti Barcode');
        $this->actingAs($this->cssd)->get('/cssd/distribusi')->assertOk()->assertSee('Distribusi Alat Steril');
    }

    #[Test]
    public function halaman_cetak_label_menghasilkan_qr_svg(): void
    {
        $response = $this->actingAs($this->cssd)
            ->get('/cssd/label?order='.$this->order->id)
            ->assertOk()
            ->assertSee($this->batch->public_code);

        // QR dirender sebagai SVG inline, bukan gambar eksternal.
        $this->assertStringContainsString('<svg', $response->getContent());
    }

    #[Test]
    public function halaman_riwayat_alat_menampilkan_jejak_audit(): void
    {
        $this->actingAs($this->cssd)
            ->get("/alat/{$this->batch->id}")
            ->assertOk()
            ->assertSee($this->batch->public_code)
            ->assertSee('Riwayat Perpindahan')
            ->assertSee('Pendataan CSSD')
            ->assertSee($this->cssd->name);
    }

    #[Test]
    public function seluruh_halaman_admin_render_dengan_data(): void
    {
        $this->actingAs($this->admin)->get('/admin')->assertOk()->assertSee('Posisi Alat per Zona');
        $this->actingAs($this->admin)->get('/admin/telusur')->assertOk()->assertSee($this->batch->public_code);
        $this->actingAs($this->admin)->get('/admin/unit')->assertOk()->assertSee('Instalasi Bedah Sentral');
        $this->actingAs($this->admin)->get('/admin/alat')->assertOk()->assertSee('Gunting Mayo 17 cm');
        $this->actingAs($this->admin)->get('/admin/set-alat')->assertOk()->assertSee('Set Laparotomi');
        $this->actingAs($this->admin)->get('/admin/pengguna')->assertOk()->assertSee('cssd1@rskemenkes.test');
    }

    #[Test]
    public function pusat_notifikasi_menampilkan_notifikasi_pendataan(): void
    {
        $this->actingAs($this->nakes)
            ->get('/notifikasi')
            ->assertOk()
            ->assertSee('Pendataan CSSD selesai')
            ->assertSee($this->order->order_number);
    }

    #[Test]
    public function nakes_tidak_bisa_membuka_halaman_alat_milik_unit_lain(): void
    {
        $foreign = ItemBatch::create([
            'public_code' => 'CSSD-ZZZZZZZZ',
            'batch_type' => \App\Enums\BatchType::Set,
            'instrument_set_id' => InstrumentSet::first()->id,
            'quantity' => 1,
            'status' => ItemBatchStatus::InStorage,
            'status_changed_at' => now(),
            'origin_unit_id' => Unit::firstWhere('code', 'ICU')->id,
        ]);

        $this->actingAs($this->nakes)->get("/alat/{$foreign->id}")->assertForbidden();
        $this->actingAs($this->nakes)->get("/alat/{$this->batch->id}")->assertOk();
    }
}
