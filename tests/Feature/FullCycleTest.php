<?php

namespace Tests\Feature;

use App\Enums\DeliveryMethod;
use App\Enums\DeliveryOrderStatus;
use App\Enums\ItemBatchStatus;
use App\Enums\ScanStation;
use App\Enums\UserRole;
use App\Livewire\Cssd\OrderIntake;
use App\Livewire\Cssd\ScanStation as ScanStationComponent;
use App\Livewire\Unit\OrderCreate;
use App\Livewire\Unit\PickupInbox;
use App\Models\DeliveryOrder;
use App\Models\InstrumentSet;
use App\Models\Item;
use App\Models\ItemBatch;
use App\Models\Pickup;
use App\Models\Unit;
use App\Models\User;
use App\Notifications\IntakeRecorded;
use App\Notifications\ItemsReadyForPickup;
use App\Services\PickupService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Menelusuri satu siklus penuh persis seperti di lapangan:
 * unit kirim -> CSSD data -> cuci -> keringkan -> cek bersih -> kemas ->
 * sterilisasi -> cek label -> gudang -> serah terima -> unit konfirmasi -> dipakai.
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
    public function siklus_penuh_dari_order_sampai_alat_kembali_dipakai_unit(): void
    {
        Notification::fake();

        // --- 1b: unit membuat order, tanpa rincian alat ---
        Livewire::actingAs($this->nakes)
            ->test(OrderCreate::class)
            ->set('courier_name', 'Budi Santoso')
            ->set('sent_at', now()->format('Y-m-d\TH:i'))
            ->set('box_count', 3)
            ->call('save')
            ->assertHasNoErrors();

        $order = DeliveryOrder::firstWhere('origin_unit_id', $this->ibs->id);

        $this->assertNotNull($order);
        $this->assertSame(DeliveryOrderStatus::PendingCssdIntake, $order->status);
        $this->assertStringStartsWith('DO-', $order->order_number);
        $this->assertFalse($order->detailVisibleToUnit(), 'Rincian belum boleh terlihat sebelum CSSD mendata.');

        // --- 1c: CSSD mendata isi kiriman (Per Set + Per Barang) ---
        $setMinor = InstrumentSet::firstWhere('code', 'SET-MINOR');
        $gunting = Item::firstWhere('code', 'ALT-002');

        Livewire::actingAs($this->cssd)
            ->test(OrderIntake::class, ['order' => $order])
            ->set('lines', [
                ['line_type' => 'set', 'instrument_set_id' => (string) $setMinor->id, 'item_id' => '', 'quantity' => 2, 'notes' => ''],
                ['line_type' => 'individual', 'instrument_set_id' => '', 'item_id' => (string) $gunting->id, 'quantity' => 5, 'notes' => 'gagang aus'],
            ])
            ->call('save')
            ->assertHasNoErrors();

        $order->refresh();

        $this->assertSame(DeliveryOrderStatus::IntakeRecorded, $order->status);
        $this->assertTrue($order->detailVisibleToUnit(), 'Rincian harus terbuka setelah pendataan.');
        $this->assertSame($this->cssd->id, $order->intake_recorded_by_user_id);

        Notification::assertSentTo($this->nakes, IntakeRecorded::class);

        // 2 set -> 2 label terpisah; 5 gunting lepasan -> 1 label berisi 5 pcs.
        $batches = $order->itemBatches()->orderBy('id')->get();
        $this->assertCount(3, $batches);
        $this->assertSame(2, $batches->where('batch_type.value', 'set')->count());
        $this->assertSame(5, $batches->firstWhere('batch_type.value', 'individual')->quantity);

        foreach ($batches as $batch) {
            $this->assertSame(ItemBatchStatus::ReturnedDirty, $batch->status);
            $this->assertMatchesRegularExpression('/^CSSD-[A-Z2-9]{8}$/', $batch->public_code);
            $this->assertSame(1, $batch->events()->count(), 'Pembuatan batch harus meninggalkan jejak audit.');
        }

        // --- 1d: scan tiap tahap di stasiun CSSD ---
        $stations = [
            [ScanStation::Washing, ItemBatchStatus::DirtyZoneWashing],
            [ScanStation::Drying, ItemBatchStatus::DirtyZoneDrying],
            [ScanStation::CleanlinessCheck, ItemBatchStatus::CleanlinessCheckPending],
            [ScanStation::CleanlinessPass, ItemBatchStatus::CleanPendingPack],
            [ScanStation::Packaging, ItemBatchStatus::Packaging],
            [ScanStation::Sterilizing, ItemBatchStatus::Sterilizing],
            [ScanStation::SterileCheck, ItemBatchStatus::SterileCheckPending],
            [ScanStation::StoragePass, ItemBatchStatus::InStorage],
        ];

        foreach ($stations as [$station, $expected]) {
            $component = Livewire::actingAs($this->cssd)
                ->test(ScanStationComponent::class)
                ->set('station', $station->value);

            foreach ($batches as $batch) {
                $component->call('handleScan', $batch->public_code, 'hid_scanner');
            }

            foreach ($batches as $batch) {
                $this->assertSame(
                    $expected,
                    $batch->fresh()->status,
                    "Setelah stasiun {$station->label()} status seharusnya {$expected->label()}."
                );
            }
        }

        // Order otomatis berpindah ke "Sedang Diproses" begitu alat bergerak.
        $this->assertSame(DeliveryOrderStatus::Processing, $order->fresh()->status);

        // --- 1e: CSSD menyerahkan, unit mengonfirmasi ---
        $pickup = app(PickupService::class)->dispatch(
            staff: $this->cssd,
            unitId: $this->ibs->id,
            batchIds: $batches->pluck('id')->all(),
            method: DeliveryMethod::UnitPickup,
            receiverName: 'Ns. Rina',
        );

        Notification::assertSentTo($this->nakes, ItemsReadyForPickup::class);

        foreach ($batches as $batch) {
            $this->assertSame(ItemBatchStatus::ReadyForPickup, $batch->fresh()->status);
        }

        Livewire::actingAs($this->nakes)
            ->test(PickupInbox::class)
            ->call('confirm', $pickup->id);

        $pickup->refresh();
        $this->assertNotNull($pickup->confirmed_at);
        $this->assertSame($this->nakes->id, $pickup->confirmed_by_user_id);

        foreach ($batches as $batch) {
            $this->assertSame(ItemBatchStatus::PickedUp, $batch->fresh()->status);
        }

        // Seluruh alat sudah kembali ke unit -> order selesai.
        $this->assertSame(DeliveryOrderStatus::Completed, $order->fresh()->status);

        // --- unit menandai alat mulai dipakai ---
        $first = $batches->first();

        Livewire::actingAs($this->nakes)
            ->test(PickupInbox::class)
            ->set('code', $first->public_code)
            ->call('markInUse');

        $this->assertSame(ItemBatchStatus::InUse, $first->fresh()->status);

        // --- jejak audit lengkap sejak awal ---
        // 1 pembuatan + 8 stasiun CSSD + siap diambil + diterima unit + dipakai.
        $events = $first->fresh()->events;
        $this->assertSame(12, $events->count(), 'Setiap perpindahan harus tercatat.');
        $this->assertNull($events->first()->from_status, 'Jejak pertama adalah pembuatan batch.');
        $this->assertSame(ItemBatchStatus::InUse, $events->last()->to_status);

        foreach ($events as $event) {
            $this->assertNotNull($event->actor_user_id, 'Setiap jejak wajib punya pelaku.');
            $this->assertNotNull($event->occurred_at, 'Setiap jejak wajib punya waktu.');
        }
    }

    #[Test]
    public function scan_di_tahap_yang_salah_ditolak_dan_status_tidak_berubah(): void
    {
        $batch = $this->makeBatchAt(ItemBatchStatus::ReturnedDirty);

        Livewire::actingAs($this->cssd)
            ->test(ScanStationComponent::class)
            ->set('station', ScanStation::StoragePass->value)
            ->call('handleScan', $batch->public_code, 'hid_scanner')
            ->assertSet('log.0.status', 'error');

        $this->assertSame(
            ItemBatchStatus::ReturnedDirty,
            $batch->fresh()->status,
            'Alat kotor tidak boleh melompat langsung ke gudang steril.'
        );
    }

    #[Test]
    public function scan_ganda_di_stasiun_yang_sama_tidak_membuat_jejak_kembar(): void
    {
        $batch = $this->makeBatchAt(ItemBatchStatus::ReturnedDirty);
        $before = $batch->events()->count();

        $component = Livewire::actingAs($this->cssd)
            ->test(ScanStationComponent::class)
            ->set('station', ScanStation::Washing->value);

        $component->call('handleScan', $batch->public_code, 'hid_scanner');
        $component->call('handleScan', $batch->public_code, 'hid_scanner');

        $this->assertSame(ItemBatchStatus::DirtyZoneWashing, $batch->fresh()->status);
        $this->assertSame($before + 1, $batch->fresh()->events()->count(), 'Scan berulang tidak boleh menambah jejak.');
    }

    #[Test]
    public function kode_tidak_dikenal_dilaporkan_sebagai_kesalahan(): void
    {
        Livewire::actingAs($this->cssd)
            ->test(ScanStationComponent::class)
            ->set('station', ScanStation::Washing->value)
            ->call('handleScan', 'CSSD-TIDAKADA', 'hid_scanner')
            ->assertSet('log.0.status', 'error');
    }

    #[Test]
    public function cek_kebersihan_gagal_mengembalikan_alat_ke_pencucian(): void
    {
        $batch = $this->makeBatchAt(ItemBatchStatus::CleanlinessCheckPending);

        Livewire::actingAs($this->cssd)
            ->test(ScanStationComponent::class)
            ->set('station', ScanStation::Rewash->value)
            ->call('handleScan', $batch->public_code, 'hid_scanner')
            ->assertSet('log.0.status', 'success');

        $this->assertSame(ItemBatchStatus::DirtyZoneWashing, $batch->fresh()->status);
    }

    #[Test]
    public function nakes_tidak_bisa_menjalankan_stasiun_scan(): void
    {
        $this->actingAs($this->nakes)->get('/cssd/scan')->assertForbidden();
    }

    #[Test]
    public function unit_lain_tidak_bisa_melihat_order_milik_unit_berbeda(): void
    {
        $order = DeliveryOrder::create([
            'order_number' => 'DO-TEST-0001',
            'origin_unit_id' => $this->ibs->id,
            'submitted_by_user_id' => $this->nakes->id,
            'courier_name' => 'Budi',
            'sent_at' => now(),
            'box_count' => 1,
            'status' => DeliveryOrderStatus::PendingCssdIntake,
        ]);

        $igdNakes = User::firstWhere('email', 'igd@rskemenkes.test');

        $this->actingAs($igdNakes)->get("/unit/order/{$order->id}")->assertForbidden();
        $this->actingAs($this->nakes)->get("/unit/order/{$order->id}")->assertOk();
    }

    #[Test]
    public function admin_bisa_mengoreksi_status_dan_koreksinya_tercatat(): void
    {
        $batch = $this->makeBatchAt(ItemBatchStatus::DirtyZoneWashing);
        $admin = User::firstWhere('role', UserRole::Admin);

        Livewire::actingAs($admin)
            ->test(\App\Livewire\Shared\BatchShow::class, ['batch' => $batch])
            ->set('overrideStatus', ItemBatchStatus::Lost->value)
            ->set('overrideReason', 'Tidak ditemukan saat stok opname bulanan.')
            ->call('applyOverride')
            ->assertHasNoErrors();

        $batch->refresh();
        $this->assertSame(ItemBatchStatus::Lost, $batch->status);

        $override = $batch->events()->where('is_admin_override', true)->first();
        $this->assertNotNull($override, 'Koreksi admin wajib meninggalkan jejak bertanda override.');
        $this->assertSame($admin->id, $override->actor_user_id);
        $this->assertStringContainsString('stok opname', $override->note);
    }

    #[Test]
    public function koreksi_admin_tanpa_alasan_ditolak(): void
    {
        $batch = $this->makeBatchAt(ItemBatchStatus::DirtyZoneWashing);
        $admin = User::firstWhere('role', UserRole::Admin);

        Livewire::actingAs($admin)
            ->test(\App\Livewire\Shared\BatchShow::class, ['batch' => $batch])
            ->set('overrideStatus', ItemBatchStatus::Lost->value)
            ->set('overrideReason', '')
            ->call('applyOverride')
            ->assertHasErrors('overrideReason');

        $this->assertSame(ItemBatchStatus::DirtyZoneWashing, $batch->fresh()->status);
    }

    #[Test]
    public function jejak_audit_tidak_bisa_diubah_maupun_dihapus(): void
    {
        $batch = $this->makeBatchAt(ItemBatchStatus::ReturnedDirty);
        $event = $batch->events()->first();

        $this->expectException(\RuntimeException::class);
        $event->update(['note' => 'diubah diam-diam']);
    }

    private function makeBatchAt(ItemBatchStatus $status): ItemBatch
    {
        $batch = ItemBatch::create([
            'public_code' => app(\App\Services\PublicCodeGenerator::class)->generate(),
            'batch_type' => \App\Enums\BatchType::Set,
            'instrument_set_id' => InstrumentSet::first()->id,
            'quantity' => 1,
            'status' => $status,
            'status_changed_at' => now(),
            'origin_unit_id' => $this->ibs->id,
        ]);

        app(\App\Services\ItemBatchTransitionService::class)->recordCreation($batch, $this->cssd);

        return $batch;
    }
}
