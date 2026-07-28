<?php

namespace Tests\Feature;

use App\Enums\UnitType;
use App\Enums\UserRole;
use App\Livewire\Auth\Login;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(UserRole $role, bool $active = true): User
    {
        $unit = Unit::create([
            'code' => 'CSSD', 'name' => 'CSSD', 'type' => UnitType::Cssd, 'is_active' => true,
        ]);

        return User::create([
            'name' => 'Petugas Uji',
            'email' => 'uji@rskemenkes.test',
            'password' => 'rahasia123',
            'role' => $role,
            'unit_id' => $role->requiresUnit() ? $unit->id : null,
            'is_active' => $active,
        ]);
    }

    #[Test]
    public function halaman_login_bisa_dibuka(): void
    {
        $this->get('/login')->assertOk()->assertSee('Masuk');
    }

    #[Test]
    public function kredensial_benar_membuat_user_masuk_ke_dashboard_perannya(): void
    {
        $this->makeUser(UserRole::CssdStaff);

        Livewire::test(Login::class)
            ->set('email', 'uji@rskemenkes.test')
            ->set('password', 'rahasia123')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect(route('cssd.dashboard'));

        $this->assertAuthenticated();
    }

    #[Test]
    public function kredensial_salah_ditolak(): void
    {
        $this->makeUser(UserRole::CssdStaff);

        Livewire::test(Login::class)
            ->set('email', 'uji@rskemenkes.test')
            ->set('password', 'salah-total')
            ->call('login')
            ->assertHasErrors('email');

        $this->assertGuest();
    }

    #[Test]
    public function akun_nonaktif_tidak_bisa_login_walau_sandinya_benar(): void
    {
        $this->makeUser(UserRole::CssdStaff, active: false);

        Livewire::test(Login::class)
            ->set('email', 'uji@rskemenkes.test')
            ->set('password', 'rahasia123')
            ->call('login')
            ->assertHasErrors('email');

        $this->assertGuest();
    }

    #[Test]
    public function user_bisa_keluar(): void
    {
        $user = $this->makeUser(UserRole::Nakes);

        $this->actingAs($user)->post('/logout')->assertRedirect(route('login'));

        $this->assertGuest();
    }

    #[Test]
    public function login_dibatasi_setelah_lima_percobaan_gagal(): void
    {
        $this->makeUser(UserRole::Nakes);

        $component = Livewire::test(Login::class)
            ->set('email', 'uji@rskemenkes.test')
            ->set('password', 'salah');

        for ($i = 0; $i < 5; $i++) {
            $component->call('login');
        }

        $component->call('login')->assertHasErrors('email');

        $this->assertFalse(Auth::check());
    }
}
