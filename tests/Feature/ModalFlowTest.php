<?php

namespace Tests\Feature;

use App\Enums\UnitType;
use App\Enums\UserRole;
use App\Livewire\Admin\UnitManager;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Modal form dipakai di keempat layar master data, jadi perilaku buka/tutup/reset
 * diuji sekali di sini sebagai perwakilan pola tersebut.
 */
class ModalFlowTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin Uji',
            'email' => 'admin@rskemenkes.test',
            'password' => 'rahasia123',
            'role' => UserRole::Admin,
            'unit_id' => null,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function modal_tertutup_saat_pertama_dibuka_dan_terbuka_setelah_klik_tambah(): void
    {
        Livewire::actingAs($this->admin())
            ->test(UnitManager::class)
            ->assertSet('showForm', false)
            ->call('create')
            ->assertSet('showForm', true)
            ->assertSet('editingId', null);
    }

    #[Test]
    public function klik_ubah_mengisi_form_dengan_data_yang_dipilih(): void
    {
        $unit = Unit::create([
            'code' => 'IBS', 'name' => 'Instalasi Bedah Sentral',
            'type' => UnitType::IbsOk, 'is_active' => true,
        ]);

        Livewire::actingAs($this->admin())
            ->test(UnitManager::class)
            ->call('edit', $unit->id)
            ->assertSet('showForm', true)
            ->assertSet('editingId', $unit->id)
            ->assertSet('code', 'IBS')
            ->assertSet('name', 'Instalasi Bedah Sentral')
            ->assertSet('type', 'ibs_ok');
    }

    #[Test]
    public function event_close_modal_menutup_dan_mengosongkan_form(): void
    {
        $unit = Unit::create([
            'code' => 'IBS', 'name' => 'IBS', 'type' => UnitType::IbsOk, 'is_active' => true,
        ]);

        Livewire::actingAs($this->admin())
            ->test(UnitManager::class)
            ->call('edit', $unit->id)
            ->assertSet('code', 'IBS')
            ->dispatch('close-modal')
            ->assertSet('showForm', false)
            ->assertSet('editingId', null)
            ->assertSet('code', '');
    }

    #[Test]
    public function form_tidak_membawa_sisa_data_dari_edit_sebelumnya(): void
    {
        $unit = Unit::create([
            'code' => 'IBS', 'name' => 'IBS', 'type' => UnitType::IbsOk, 'is_active' => true,
        ]);

        Livewire::actingAs($this->admin())
            ->test(UnitManager::class)
            ->call('edit', $unit->id)
            ->call('create')
            ->assertSet('editingId', null)
            ->assertSet('code', '')
            ->assertSet('name', '')
            ->assertSet('type', 'ward');
    }
}
