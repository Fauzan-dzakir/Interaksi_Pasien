<?php

namespace Tests\Feature;

use App\Enums\MaterialSensitivity;
use App\Enums\UnitType;
use App\Enums\UserRole;
use App\Livewire\Admin\InstrumentSetManager;
use App\Livewire\Admin\UnitManager;
use App\Livewire\Admin\UserManager;
use App\Models\InstrumentSet;
use App\Models\Item;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MasterDataTest extends TestCase
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
    public function admin_bisa_menambah_unit(): void
    {
        Livewire::actingAs($this->admin())
            ->test(UnitManager::class)
            ->call('create')
            ->set('code', 'IBS')
            ->set('name', 'Instalasi Bedah Sentral')
            ->set('type', UnitType::IbsOk->value)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('units', ['code' => 'IBS', 'type' => 'ibs_ok', 'is_active' => true]);
    }

    #[Test]
    public function kode_unit_tidak_boleh_kembar(): void
    {
        Unit::create(['code' => 'IBS', 'name' => 'IBS', 'type' => UnitType::IbsOk, 'is_active' => true]);

        Livewire::actingAs($this->admin())
            ->test(UnitManager::class)
            ->call('create')
            ->set('code', 'IBS')
            ->set('name', 'IBS Duplikat')
            ->call('save')
            ->assertHasErrors('code');

        $this->assertSame(1, Unit::count());
    }

    #[Test]
    public function unit_dinonaktifkan_bukan_dihapus(): void
    {
        $unit = Unit::create(['code' => 'ICU', 'name' => 'ICU', 'type' => UnitType::Ward, 'is_active' => true]);

        Livewire::actingAs($this->admin())
            ->test(UnitManager::class)
            ->call('toggleActive', $unit->id);

        $this->assertDatabaseHas('units', ['id' => $unit->id, 'is_active' => false]);
        $this->assertSame(1, Unit::count());
    }

    #[Test]
    public function set_alat_menyimpan_komposisi_isinya(): void
    {
        $gunting = Item::create([
            'code' => 'ALT-001', 'name' => 'Gunting Mayo', 'category' => 'Gunting',
            'material_sensitivity' => MaterialSensitivity::HeatWaterResistant, 'is_active' => true,
        ]);
        $klem = Item::create([
            'code' => 'ALT-002', 'name' => 'Klem Pean', 'category' => 'Klem',
            'material_sensitivity' => MaterialSensitivity::HeatWaterResistant, 'is_active' => true,
        ]);

        Livewire::actingAs($this->admin())
            ->test(InstrumentSetManager::class)
            ->call('create')
            ->set('code', 'SET-MINOR')
            ->set('name', 'Set Bedah Minor')
            ->set('setItems', [
                ['item_id' => (string) $gunting->id, 'quantity' => 1],
                ['item_id' => (string) $klem->id, 'quantity' => 4],
            ])
            ->call('save')
            ->assertHasNoErrors();

        $set = InstrumentSet::with('items')->firstWhere('code', 'SET-MINOR');

        $this->assertSame(2, $set->items->count());
        $this->assertSame(5, $set->totalItemCount());
    }

    #[Test]
    public function satu_jenis_alat_tidak_boleh_dobel_dalam_satu_set(): void
    {
        $item = Item::create([
            'code' => 'ALT-001', 'name' => 'Gunting Mayo', 'category' => 'Gunting',
            'material_sensitivity' => MaterialSensitivity::HeatWaterResistant, 'is_active' => true,
        ]);

        Livewire::actingAs($this->admin())
            ->test(InstrumentSetManager::class)
            ->call('create')
            ->set('code', 'SET-DOBEL')
            ->set('name', 'Set Dobel')
            ->set('setItems', [
                ['item_id' => (string) $item->id, 'quantity' => 1],
                ['item_id' => (string) $item->id, 'quantity' => 2],
            ])
            ->call('save')
            ->assertHasErrors('setItems');

        $this->assertDatabaseMissing('instrument_sets', ['code' => 'SET-DOBEL']);
    }

    #[Test]
    public function peran_selain_admin_wajib_punya_unit(): void
    {
        Livewire::actingAs($this->admin())
            ->test(UserManager::class)
            ->call('create')
            ->set('name', 'Perawat Tanpa Unit')
            ->set('email', 'tanpa-unit@rskemenkes.test')
            ->set('role', UserRole::Nakes->value)
            ->set('unit_id', '')
            ->set('password', 'rahasia123')
            ->call('save')
            ->assertHasErrors('unit_id');

        $this->assertDatabaseMissing('users', ['email' => 'tanpa-unit@rskemenkes.test']);
    }

    #[Test]
    public function admin_dibuat_tanpa_unit_dan_sandinya_ter_hash(): void
    {
        Livewire::actingAs($this->admin())
            ->test(UserManager::class)
            ->call('create')
            ->set('name', 'Admin Kedua')
            ->set('email', 'admin2@rskemenkes.test')
            ->set('role', UserRole::Admin->value)
            ->set('password', 'rahasia123')
            ->call('save')
            ->assertHasNoErrors();

        $user = User::firstWhere('email', 'admin2@rskemenkes.test');

        $this->assertNull($user->unit_id);
        $this->assertNotSame('rahasia123', $user->password);
        $this->assertTrue(password_verify('rahasia123', $user->password));
    }

    #[Test]
    public function admin_tidak_bisa_menonaktifkan_akunnya_sendiri(): void
    {
        $admin = $this->admin();

        Livewire::actingAs($admin)
            ->test(UserManager::class)
            ->call('toggleActive', $admin->id)
            ->assertHasErrors('general');

        $this->assertDatabaseHas('users', ['id' => $admin->id, 'is_active' => true]);
    }
}
