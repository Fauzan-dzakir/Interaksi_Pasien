<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\AssetCode;
use App\Models\Batch;
use App\Models\Order;
use App\Models\ReturnShipment;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\InventorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Seeder yang dipakai saat sistem mulai dipakai sungguhan hanya boleh mendaftarkan
 * inventaris. Transaksi apa pun harus lahir dari aplikasi, bukan dari data contoh.
 */
class InventorySeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->seed(InventorySeeder::class);
    }

    #[Test]
    public function tidak_membuat_pesanan_maupun_pengembalian_palsu(): void
    {
        $this->assertSame(0, Order::count(), 'Seeder tidak boleh membuat pesanan.');
        $this->assertSame(0, ReturnShipment::count(), 'Seeder tidak boleh membuat pengembalian.');
    }

    #[Test]
    public function mendaftarkan_aset_beserta_barcode_dan_jejak_auditnya(): void
    {
        $this->assertGreaterThan(0, Asset::count());

        // Semua aset lahir siap pakai di gudang steril dan bebas dari batch mana pun.
        $this->assertSame(Asset::count(), Asset::where('status', AssetStatus::Available)->count());
        $this->assertSame(Asset::count(), Asset::whereNull('batch_id')->count());

        // Satu aset tepat punya satu barcode aktif.
        $this->assertSame(Asset::count(), AssetCode::active()->count());
        $this->assertSame(0, Asset::whereNull('current_code')->count());

        foreach (Asset::with('events')->get() as $asset) {
            $this->assertGreaterThan(0, $asset->events->count(), 'Setiap aset wajib punya jejak audit sejak didaftarkan.');
        }
    }

    #[Test]
    public function membuat_batch_kosong_untuk_tiap_unit(): void
    {
        $batches = Batch::withCount('assets')->get();

        $this->assertGreaterThan(0, $batches->count());

        foreach ($batches as $batch) {
            $this->assertSame(0, $batch->assets_count, 'Batch harus lahir kosong, terisi saat unit memesan.');
            $this->assertTrue($batch->is_active);
        }
    }

    #[Test]
    public function aman_dijalankan_dua_kali_tanpa_menggandakan_batch(): void
    {
        $before = Batch::count();

        $this->seed(InventorySeeder::class);

        $this->assertSame($before, Batch::count(), 'Batch tidak boleh tergandakan bila seeder diulang.');
    }
}
