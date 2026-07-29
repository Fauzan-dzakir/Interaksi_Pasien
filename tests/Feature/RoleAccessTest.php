<?php

namespace Tests\Feature;

use App\Enums\UnitType;
use App\Enums\UserRole;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Menguji batas hak akses antar peran, inti dari keamanan Fase 1a.
 */
class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(UserRole $role, bool $active = true): User
    {
        $unit = Unit::create([
            'code' => 'U'.fake()->unique()->numberBetween(1, 99999),
            'name' => 'Unit Uji',
            'type' => UnitType::Ward,
            'is_active' => true,
        ]);

        return User::create([
            'name' => 'Pengguna Uji',
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password',
            'role' => $role,
            'unit_id' => $role->requiresUnit() ? $unit->id : null,
            'is_active' => $active,
        ]);
    }

    #[Test]
    public function tamu_diarahkan_ke_halaman_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
        $this->get('/admin')->assertRedirect(route('login'));
    }

    #[Test]
    public function setiap_peran_diarahkan_ke_dashboard_masing_masing(): void
    {
        $cases = [
            [UserRole::Admin, '/admin'],
            [UserRole::CssdStaff, '/cssd'],
            [UserRole::Nakes, '/unit'],
        ];

        foreach ($cases as [$role, $expectedPath]) {
            $this->actingAs($this->makeUser($role))
                ->get('/')
                ->assertRedirect(url($expectedPath));
        }
    }

    #[Test]
    public function nakes_tidak_bisa_masuk_area_admin_maupun_cssd(): void
    {
        $nakes = $this->makeUser(UserRole::Nakes);

        $this->actingAs($nakes)->get('/admin')->assertForbidden();
        $this->actingAs($nakes)->get('/admin/pengguna')->assertForbidden();
        $this->actingAs($nakes)->get('/cssd')->assertForbidden();
    }

    #[Test]
    public function petugas_cssd_tidak_bisa_masuk_area_admin_dan_unit(): void
    {
        $cssd = $this->makeUser(UserRole::CssdStaff);

        $this->actingAs($cssd)->get('/admin')->assertForbidden();
        $this->actingAs($cssd)->get('/unit')->assertForbidden();
        $this->actingAs($cssd)->get('/cssd')->assertOk();
    }

    #[Test]
    public function admin_bisa_membuka_seluruh_halaman_admin_dan_area_cssd(): void
    {
        $admin = $this->makeUser(UserRole::Admin);

        foreach (['/admin', '/admin/unit', '/admin/alat', '/admin/set-alat', '/admin/pengguna'] as $path) {
            $this->actingAs($admin)->get($path)->assertOk();
        }

        // Admin berwenang mengoreksi human error, jadi area CSSD ikut terbuka.
        $this->actingAs($admin)->get('/cssd')->assertOk();

        // Dashboard unit tetap tertutup: isinya ter-scope ke unit, sedangkan Admin lintas unit.
        $this->actingAs($admin)->get('/unit')->assertForbidden();
    }

    #[Test]
    public function akun_nonaktif_ditolak_walau_sudah_terautentikasi(): void
    {
        $nonaktif = $this->makeUser(UserRole::CssdStaff, active: false);

        $this->actingAs($nonaktif)->get('/cssd')->assertForbidden();
    }
}
