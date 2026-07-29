<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Enums\AssetType;
use App\Enums\FulfillmentMethod;
use App\Enums\OrderStatus;
use App\Enums\ScanStation;
use App\Enums\SterilizationMethod;
use App\Enums\UserRole;
use App\Livewire\Cssd\NewBarcodeScan;
use App\Livewire\Cssd\OrderPrepare;
use App\Livewire\Cssd\ScanStation as ScanStationComponent;
use App\Livewire\Unit\OrderCreate;
use App\Livewire\Unit\UsageScan;
use App\Models\Asset;
use App\Models\Batch;
use App\Models\InstrumentSet;
use App\Models\Item;
use App\Models\Order;
use App\Models\ReturnShipment;
use App\Models\Unit;
use App\Models\User;
use App\Notifications\OrderReady;
use App\Notifications\ReturnAwaitingConfirmation;
use App\Services\AssetRegistrationService;
use App\Services\PublicCodeGenerator;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Menelusuri satu siklus penuh sesuai alur nyata CSSD:
 * stok tersedia -> unit checkout -> CSSD siapkan & serah (foto) -> unit terima
 * -> dipakai -> dikembalikan -> CSSD konfirmasi -> cuci -> barcode baru + checklist set
 * -> sterilisasi -> selesai -> kembali tersedia.
 */
class FullCycleTest extends TestCase
{
    use RefreshDatabase;

    private User $nakes;

    private User $cssd;

    private Unit $ibs;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->nakes = User::firstWhere('email', 'ibs@rskemenkes.test');
        $this->cssd = User::firstWhere('email', 'cssd1@rskemenkes.test');
        $this->ibs = Unit::firstWhere('code', 'IBS');
    }

    #[Test]
    public function siklus_penuh_pesanan_cuci_dari_unit_sampai_alat_kembali(): void
    {
        Notification::fake();

        // Alat berada di unit, seperti kondisi nyata sebelum dikirim untuk dicuci.
        $setAsset = $this->registerSet('SET-MINOR');
        $itemAsset = $this->registerItem('ALT-002');
        $this->moveTo($setAsset, AssetStatus::AtUnit);
        $this->moveTo($itemAsset, AssetStatus::AtUnit);

        // --- Unit menandai satu alat sudah dipakai ---
        Livewire::actingAs($this->nakes)
            ->test(UsageScan::class)
            ->call('handleScan', $setAsset->current_code, 'hid_scanner')
            ->assertSet('log.0.status', 'success');

        $this->assertSame(AssetStatus::InUse, $setAsset->fresh()->status);

        // --- Unit membuat pesanan cuci: cukup foto, tanpa mendata barang ---
        Livewire::actingAs($this->nakes)
            ->test(OrderCreate::class)
            ->set('photos', [UploadedFile::fake()->image('alat-kotor.jpg')])
            ->set('notes', 'Dua alat selesai operasi pagi.')
            ->call('save')
            ->assertHasNoErrors();

        $order = Order::firstWhere('unit_id', $this->ibs->id);

        $this->assertNotNull($order);
        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertStringStartsWith('OR-', $order->order_number);
        $this->assertSame(1, $order->photos()->count(), 'Foto barang harus tersimpan.');
        $this->assertSame(0, $order->assets()->count(), 'Unit tidak mendata barang.');

        // --- CSSD men-scan alat kotor yang datang, di sinilah pendataan terjadi ---
        $prepare = Livewire::actingAs($this->cssd)->test(OrderPrepare::class, ['order' => $order]);
        $prepare->set('scanCode', $setAsset->current_code)->call('receiveScan')->assertSet('feedbackType', 'success');
        $prepare->set('scanCode', $itemAsset->current_code)->call('receiveScan')->assertSet('feedbackType', 'success');

        $order->refresh();
        $this->assertSame(OrderStatus::Preparing, $order->status);
        $this->assertSame(2, $order->assets()->count());
        $this->assertSame(AssetStatus::ReturnPending, $setAsset->fresh()->status);

        // --- Alat masuk pencucian ---
        $wash = Livewire::actingAs($this->cssd)
            ->test(ScanStationComponent::class)
            ->set('station', ScanStation::Washing->value);

        $wash->call('handleScan', $setAsset->fresh()->current_code, 'hid_scanner');
        $wash->call('handleScan', $itemAsset->fresh()->current_code, 'hid_scanner');

        $this->assertSame(AssetStatus::Washing, $setAsset->fresh()->status);

        // Barcode lama HARUS masih berlaku selama pencucian.
        $oldSetCode = $setAsset->fresh()->current_code;
        $this->assertNotNull($oldSetCode);
        $this->assertSame($setAsset->current_code, $oldSetCode);

        // --- Barcode baru + checklist isi set ---
        $newSetCode = app(PublicCodeGenerator::class)->generate();

        $scan = Livewire::actingAs($this->cssd)
            ->test(NewBarcodeScan::class)
            ->set('lookupCode', $oldSetCode)
            ->call('lookup');

        $scan->assertSet('assetId', $setAsset->id);
        $this->assertNotEmpty($scan->get('checklist'), 'Set harus memunculkan checklist isi.');

        $scan->set('newCode', $newSetCode)
            ->set('photo', \Illuminate\Http\UploadedFile::fake()->image('set.jpg'))
            ->call('save')
            ->assertHasNoErrors();

        $setAsset->refresh();
        $this->assertSame($newSetCode, $setAsset->current_code, 'Barcode baru harus menggantikan yang lama.');
        $this->assertSame(AssetStatus::Packed, $setAsset->status);
        $this->assertTrue($setAsset->is_complete);
        $this->assertSame(2, $setAsset->codes()->count(), 'Riwayat barcode harus menyimpan kode lama.');
        $this->assertNotNull($setAsset->codes()->where('code', $oldSetCode)->first()->retired_at);

        // Alat satuan juga dipasangi barcode baru.
        $newItemCode = app(PublicCodeGenerator::class)->generate();

        Livewire::actingAs($this->cssd)
            ->test(NewBarcodeScan::class)
            ->set('lookupCode', $itemAsset->fresh()->current_code)
            ->call('lookup')
            ->set('newCode', $newItemCode)
            ->call('save')
            ->assertHasNoErrors();

        // --- Sterilisasi lalu selesai ---
        foreach ([ScanStation::Sterilizing, ScanStation::Complete] as $station) {
            $comp = Livewire::actingAs($this->cssd)
                ->test(ScanStationComponent::class)
                ->set('station', $station->value)
                ->set('method', SterilizationMethod::Steam->value);

            $comp->call('handleScan', $setAsset->fresh()->current_code, 'hid_scanner');
            $comp->call('handleScan', $itemAsset->fresh()->current_code, 'hid_scanner');
        }

        $setAsset->refresh();
        $this->assertSame(AssetStatus::Available, $setAsset->status, 'Alat harus selesai steril.');
        $this->assertSame(1, $setAsset->cycle_count);
        $this->assertSame(SterilizationMethod::Steam, $setAsset->sterilization_method);

        // --- CSSD mengembalikan alat ke unit ---
        Livewire::actingAs($this->cssd)
            ->test(OrderPrepare::class, ['order' => $order->fresh()])
            ->call('handBack')
            ->assertHasNoErrors();

        $order->refresh();
        $this->assertSame(OrderStatus::ReadyForPickup, $order->status);
        $this->assertSame(AssetStatus::ReadyForHandover, $setAsset->fresh()->status);
        Notification::assertSentTo($this->nakes, OrderReady::class);

        // --- Unit mengonfirmasi alat sudah kembali ---
        Livewire::actingAs($this->nakes)
            ->test(\App\Livewire\Unit\OrderShow::class, ['order' => $order])
            ->call('confirmReceipt');

        $order->refresh();
        $this->assertSame(OrderStatus::Received, $order->status);
        $this->assertSame(AssetStatus::AtUnit, $setAsset->fresh()->status, 'Siklus tertutup, alat kembali ke unit.');

        // --- Jejak audit lengkap & selalu punya pelaku ---
        $events = $setAsset->events;
        $this->assertGreaterThanOrEqual(10, $events->count());

        foreach ($events as $event) {
            $this->assertNotNull($event->actor_user_id, 'Setiap jejak wajib punya pelaku.');
            $this->assertNotNull($event->occurred_at, 'Setiap jejak wajib punya waktu.');
        }
    }

    #[Test]
    public function pesanan_cuci_wajib_melampirkan_foto(): void
    {
        Livewire::actingAs($this->nakes)
            ->test(OrderCreate::class)
            ->set('photos', [])
            ->call('save')
            ->assertHasErrors('photos');

        $this->assertSame(0, Order::count());
    }

    #[Test]
    public function pesanan_cito_wajib_mencantumkan_waktu_dibutuhkan(): void
    {
        Livewire::actingAs($this->nakes)
            ->test(OrderCreate::class)
            ->set('photos', [UploadedFile::fake()->image('a.jpg')])
            ->set('isCito', true)
            ->set('neededAt', '')
            ->call('save')
            ->assertHasErrors('neededAt');

        // Pesanan biasa tidak memerlukan waktu dibutuhkan.
        Livewire::actingAs($this->nakes)
            ->test(OrderCreate::class)
            ->set('photos', [UploadedFile::fake()->image('b.jpg')])
            ->set('isCito', false)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNull(Order::latest('id')->first()->needed_at);
    }

    #[Test]
    public function cssd_menolak_scan_alat_yang_tidak_sedang_di_unit(): void
    {
        $order = app(\App\Services\OrderService::class)->create(
            requester: $this->nakes,
            photoPaths: ['order-photos/uji.jpg'],
        );

        // Alat masih tersedia di gudang, bukan alat kotor dari unit.
        $asset = $this->registerSet('SET-MINOR');

        Livewire::actingAs($this->cssd)
            ->test(OrderPrepare::class, ['order' => $order])
            ->set('scanCode', $asset->current_code)
            ->call('receiveScan')
            ->assertSet('feedbackType', 'error');

        $this->assertSame(0, $order->fresh()->assets()->count());
    }

    #[Test]
    public function pengembalian_ditolak_bila_masih_ada_alat_belum_selesai(): void
    {
        $order = app(\App\Services\OrderService::class)->create(
            requester: $this->nakes,
            photoPaths: ['order-photos/uji.jpg'],
        );

        $asset = $this->registerItem('ALT-002');
        $this->moveTo($asset, AssetStatus::AtUnit);

        app(\App\Services\OrderService::class)->receiveDirtyAsset($order, $asset, $this->cssd);

        // Alat baru sampai tahap ReturnPending, jadi belum boleh dikembalikan.
        $this->expectException(\InvalidArgumentException::class);
        app(\App\Services\OrderService::class)->handBack($order->fresh(), $this->cssd);
    }

    #[Test]
    public function barcode_lama_tetap_bisa_discan_selama_pencucian(): void
    {
        $asset = $this->registerSet('SET-MINOR');
        $oldCode = $asset->current_code;

        $this->moveTo($asset, AssetStatus::Washing);

        // Masih dikenali dengan kode lama.
        $this->assertSame($asset->id, Asset::where('current_code', $oldCode)->first()?->id);

        $newCode = app(PublicCodeGenerator::class)->generate();

        Livewire::actingAs($this->cssd)
            ->test(NewBarcodeScan::class)
            ->set('lookupCode', $oldCode)
            ->call('lookup')
            ->set('newCode', $newCode)
            ->set('photo', \Illuminate\Http\UploadedFile::fake()->image('set.jpg'))
            ->call('save')
            ->assertHasNoErrors();

        // Setelah barcode baru dipasang, kode lama berhenti berlaku.
        $this->assertNull(Asset::where('current_code', $oldCode)->first());
        $this->assertSame($asset->id, Asset::where('current_code', $newCode)->first()?->id);
    }

    #[Test]
    public function set_dengan_isi_disilang_ditandai_tidak_lengkap_tapi_tetap_lanjut(): void
    {
        $asset = $this->registerSet('SET-MINOR');
        $this->moveTo($asset, AssetStatus::Washing);

        $component = Livewire::actingAs($this->cssd)
            ->test(NewBarcodeScan::class)
            ->set('lookupCode', $asset->current_code)
            ->call('lookup');

        // Silang isi pertama: menandakan alat tidak ditemukan.
        $component->call('toggleContent', 0);

        $component->set('newCode', app(PublicCodeGenerator::class)->generate())
            ->set('photo', \Illuminate\Http\UploadedFile::fake()->image('set.jpg'))
            ->call('save')
            ->assertHasNoErrors();

        $asset->refresh();

        $this->assertFalse($asset->is_complete, 'Set harus ditandai tidak lengkap.');
        $this->assertSame(AssetStatus::Packed, $asset->status, 'Proses tetap boleh lanjut.');
        $this->assertSame(1, $asset->contentChecks()->where('is_present', false)->count());
    }

    #[Test]
    public function set_wajib_difoto_saat_barcode_baru_dipasang(): void
    {
        $asset = $this->registerSet('SET-MINOR');
        $this->moveTo($asset, AssetStatus::Washing);

        Livewire::actingAs($this->cssd)
            ->test(NewBarcodeScan::class)
            ->set('lookupCode', $asset->current_code)
            ->call('lookup')
            ->set('newCode', app(PublicCodeGenerator::class)->generate())
            ->call('save')
            ->assertHasErrors('photo');

        $this->assertSame(AssetStatus::Washing, $asset->fresh()->status);
    }

    #[Test]
    public function aset_yang_tidak_dipesan_ulang_keluar_dari_batch_ke_stok_bebas(): void
    {
        $batch = Batch::create([
            'code' => 'BATCH-TEST', 'name' => 'IBS Uji',
            'unit_id' => $this->ibs->id, 'is_active' => true,
        ]);

        $asset = $this->registerItem('ALT-002');
        $asset->forceFill(['batch_id' => $batch->id])->save();

        $this->moveTo($asset, AssetStatus::AtUnit);

        app(\App\Services\ReturnService::class)->send(
            sender: $this->nakes,
            assetIds: [$asset->id],
            batchId: $batch->id,
            reorderBatch: false,
        );

        $shipment = ReturnShipment::latest()->first();
        app(\App\Services\ReturnService::class)->confirmReceipt($shipment, $this->cssd);

        app(AssetRegistrationService::class)->issueNewCode(
            $asset->fresh(), app(PublicCodeGenerator::class)->generate(), $this->cssd);

        app(\App\Services\SterilizationService::class)
            ->startSterilizing($asset->fresh(), $this->cssd, SterilizationMethod::Steam);
        app(\App\Services\SterilizationService::class)->complete($asset->fresh(), $this->cssd);

        $this->assertNull($asset->fresh()->batch_id, 'Aset harus dilepas ke stok bebas.');
        $this->assertSame(AssetStatus::Available, $asset->fresh()->status);
    }

    #[Test]
    public function aset_yang_dipesan_ulang_tetap_menempel_pada_batch(): void
    {
        $batch = Batch::create([
            'code' => 'BATCH-TEST2', 'name' => 'IBS Uji 2',
            'unit_id' => $this->ibs->id, 'is_active' => true,
        ]);

        $asset = $this->registerItem('ALT-002');
        $asset->forceFill(['batch_id' => $batch->id])->save();

        $this->moveTo($asset, AssetStatus::AtUnit);

        app(\App\Services\ReturnService::class)->send(
            sender: $this->nakes,
            assetIds: [$asset->id],
            batchId: $batch->id,
            reorderBatch: true,
        );

        $shipment = ReturnShipment::latest()->first();
        app(\App\Services\ReturnService::class)->confirmReceipt($shipment, $this->cssd);

        app(AssetRegistrationService::class)->issueNewCode(
            $asset->fresh(), app(PublicCodeGenerator::class)->generate(), $this->cssd);

        app(\App\Services\SterilizationService::class)
            ->startSterilizing($asset->fresh(), $this->cssd, SterilizationMethod::Steam);
        app(\App\Services\SterilizationService::class)->complete($asset->fresh(), $this->cssd);

        $this->assertSame($batch->id, $asset->fresh()->batch_id, 'Aset harus tetap jadi langganan unit.');
    }

    #[Test]
    public function scan_di_tahap_yang_salah_ditolak_dan_status_tidak_berubah(): void
    {
        $asset = $this->registerSet('SET-MINOR');

        Livewire::actingAs($this->cssd)
            ->test(ScanStationComponent::class)
            ->set('station', ScanStation::Sterilizing->value)
            ->call('handleScan', $asset->current_code, 'hid_scanner')
            ->assertSet('log.0.status', 'error');

        $this->assertSame(AssetStatus::Available, $asset->fresh()->status);
    }

    #[Test]
    public function scan_ganda_di_stasiun_yang_sama_tidak_membuat_jejak_kembar(): void
    {
        $asset = $this->registerSet('SET-MINOR');
        $this->moveTo($asset, AssetStatus::Packed);

        $before = $asset->events()->count();

        $comp = Livewire::actingAs($this->cssd)
            ->test(ScanStationComponent::class)
            ->set('station', ScanStation::Sterilizing->value);

        $comp->call('handleScan', $asset->current_code, 'hid_scanner');
        $comp->call('handleScan', $asset->current_code, 'hid_scanner');

        $this->assertSame(AssetStatus::Sterilizing, $asset->fresh()->status);
        $this->assertSame($before + 1, $asset->fresh()->events()->count());
    }

    #[Test]
    public function barcode_baru_hanya_bisa_dipasang_saat_alat_sedang_dicuci(): void
    {
        $asset = $this->registerSet('SET-MINOR');

        Livewire::actingAs($this->cssd)
            ->test(NewBarcodeScan::class)
            ->set('lookupCode', $asset->current_code)
            ->call('lookup')
            ->assertSet('assetId', null)
            ->assertSet('feedbackType', 'error');
    }

    #[Test]
    public function unit_tidak_bisa_menandai_pakai_alat_milik_batch_unit_lain(): void
    {
        $icu = Unit::firstWhere('code', 'ICU');
        $batch = Batch::create([
            'code' => 'BATCH-ICU', 'name' => 'ICU Uji',
            'unit_id' => $icu->id, 'is_active' => true,
        ]);

        $asset = $this->registerItem('ALT-002');
        $asset->forceFill(['batch_id' => $batch->id])->save();
        $this->moveTo($asset, AssetStatus::AtUnit);

        Livewire::actingAs($this->nakes)
            ->test(UsageScan::class)
            ->call('handleScan', $asset->current_code, 'hid_scanner')
            ->assertSet('log.0.status', 'error');

        $this->assertSame(AssetStatus::AtUnit, $asset->fresh()->status);
    }

    #[Test]
    public function nakes_tidak_bisa_menjalankan_stasiun_scan_cssd(): void
    {
        $this->actingAs($this->nakes)->get('/cssd/scan')->assertForbidden();
        $this->actingAs($this->nakes)->get('/cssd/barcode-baru')->assertForbidden();
    }

    #[Test]
    public function admin_bisa_mengoreksi_status_dan_koreksinya_tercatat(): void
    {
        $asset = $this->registerSet('SET-MINOR');
        $admin = User::firstWhere('role', UserRole::Admin);

        Livewire::actingAs($admin)
            ->test(\App\Livewire\Shared\AssetShow::class, ['asset' => $asset])
            ->set('overrideStatus', AssetStatus::Lost->value)
            ->set('overrideReason', 'Tidak ditemukan saat stok opname bulanan.')
            ->call('applyOverride')
            ->assertHasNoErrors();

        $asset->refresh();
        $this->assertSame(AssetStatus::Lost, $asset->status);

        $override = $asset->events()->where('is_admin_override', true)->first();
        $this->assertNotNull($override);
        $this->assertSame($admin->id, $override->actor_user_id);
        $this->assertStringContainsString('stok opname', $override->note);
    }

    #[Test]
    public function jejak_audit_tidak_bisa_diubah_maupun_dihapus(): void
    {
        $asset = $this->registerSet('SET-MINOR');
        $event = $asset->events()->first();

        $this->expectException(\RuntimeException::class);
        $event->update(['note' => 'diubah diam-diam']);
    }

    // ---------- helper ----------

    private function registerSet(string $code): Asset
    {
        return app(AssetRegistrationService::class)->register(
            AssetType::Set,
            InstrumentSet::where('code', $code)->value('id'),
            null,
            $this->cssd,
        );
    }

    private function registerItem(string $code): Asset
    {
        return app(AssetRegistrationService::class)->register(
            AssetType::Item,
            null,
            Item::where('code', $code)->value('id'),
            $this->cssd,
        );
    }

    /** Mendorong aset melewati alur normal sampai status yang diminta. */
    private function moveTo(Asset $asset, AssetStatus $target): void
    {
        $path = [
            AssetStatus::Reserved, AssetStatus::ReadyForHandover, AssetStatus::AtUnit,
            AssetStatus::ReturnPending, AssetStatus::Washing, AssetStatus::Packed,
            AssetStatus::Sterilizing, AssetStatus::Available,
        ];

        $service = app(\App\Services\AssetTransitionService::class);

        foreach ($path as $status) {
            $service->transition($asset, $status, $this->cssd);

            if ($status === $target) {
                return;
            }
        }
    }
}
