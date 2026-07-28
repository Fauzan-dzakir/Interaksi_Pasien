<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Smoke test dengan data seeder sungguhan: memastikan halaman tidak sekadar
 * balas 200, tapi benar-benar menampilkan isi master data.
 */
class SeededSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function loginAs(string $email): User
    {
        $user = User::firstWhere('email', $email);
        $this->assertNotNull($user, "Akun seeder {$email} tidak ditemukan.");

        return $user;
    }

    #[Test]
    public function seeder_membuat_akun_untuk_ketiga_peran(): void
    {
        $this->assertSame(1, User::where('role', UserRole::Admin)->count());
        $this->assertSame(2, User::where('role', UserRole::CssdStaff)->count());
        $this->assertSame(3, User::where('role', UserRole::Nakes)->count());
    }

    #[Test]
    public function dashboard_admin_menampilkan_jumlah_master_data(): void
    {
        $this->actingAs($this->loginAs('admin@rskemenkes.test'))
            ->get('/admin')
            ->assertOk()
            ->assertSee('Dashboard Admin')
            ->assertSee('Unit Aktif')
            ->assertSee('Pengguna per Peran');
    }

    #[Test]
    public function halaman_unit_menampilkan_data_unit_hasil_seeder(): void
    {
        $this->actingAs($this->loginAs('admin@rskemenkes.test'))
            ->get('/admin/unit')
            ->assertOk()
            ->assertSee('Instalasi Bedah Sentral')
            ->assertSee('IBS / Kamar Operasi');
    }

    #[Test]
    public function halaman_katalog_alat_menampilkan_alat_dan_panduan_pembersihannya(): void
    {
        $this->actingAs($this->loginAs('admin@rskemenkes.test'))
            ->get('/admin/alat')
            ->assertOk()
            ->assertSee('Gunting Metzenbaum 18 cm')
            ->assertSee('Tahan panas &amp; tahan uap air', escape: false)
            ->assertSee('washer-disinfector');
    }

    #[Test]
    public function halaman_set_alat_menampilkan_jumlah_isi_set(): void
    {
        $this->actingAs($this->loginAs('admin@rskemenkes.test'))
            ->get('/admin/set-alat')
            ->assertOk()
            ->assertSee('Set Laparotomi')
            ->assertSee('9 jenis')
            ->assertSee('26 pcs');
    }

    #[Test]
    public function halaman_pengguna_menampilkan_peran_dan_unit_asal(): void
    {
        $this->actingAs($this->loginAs('admin@rskemenkes.test'))
            ->get('/admin/pengguna')
            ->assertOk()
            ->assertSee('Petugas CSSD')
            ->assertSee('cssd1@rskemenkes.test')
            ->assertSee('Instalasi Gawat Darurat');
    }

    #[Test]
    public function navigasi_hanya_menampilkan_menu_sesuai_peran(): void
    {
        // Nakes tidak boleh melihat menu master data sama sekali.
        $this->actingAs($this->loginAs('ibs@rskemenkes.test'))
            ->get('/unit')
            ->assertOk()
            ->assertSee('Instalasi Bedah Sentral')
            ->assertDontSee('Katalog Alat')
            ->assertDontSee('Pengguna');

        $this->actingAs($this->loginAs('cssd1@rskemenkes.test'))
            ->get('/cssd')
            ->assertOk()
            ->assertSee('Dashboard CSSD')
            ->assertDontSee('Katalog Alat');
    }
}
