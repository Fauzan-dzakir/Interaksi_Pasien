<?php

namespace App\Livewire\Admin;

use App\Enums\UnitType;
use App\Models\Unit;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class UnitManager extends Component
{
    use WithPagination;

    #[Url(as: 'q', keep: false)]
    public string $search = '';

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $code = '';

    public string $name = '';

    public string $type = 'ward';

    public bool $is_active = true;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $unit = Unit::findOrFail($id);

        $this->editingId = $unit->id;
        $this->code = $unit->code;
        $this->name = $unit->name;
        $this->type = $unit->type->value;
        $this->is_active = $unit->is_active;

        $this->resetErrorBag();
        $this->showForm = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'code' => ['required', 'string', 'max:32', Rule::unique('units', 'code')->ignore($this->editingId)],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(UnitType::class)],
            'is_active' => ['boolean'],
        ]);

        Unit::updateOrCreate(['id' => $this->editingId], $data);

        $this->showForm = false;
        $this->resetForm();

        session()->flash('status', 'Data unit berhasil disimpan.');
    }

    /**
     * Master data tidak pernah dihapus permanen — hanya dinonaktifkan, supaya
     * riwayat order/pendataan lama yang mengacu ke unit ini tetap utuh.
     */
    public function toggleActive(int $id): void
    {
        $unit = Unit::findOrFail($id);
        $unit->update(['is_active' => ! $unit->is_active]);

        session()->flash('status', "Unit \"{$unit->name}\" kini ".($unit->is_active ? 'aktif' : 'nonaktif').'.');
    }

    #[On('close-modal')]
    public function closeModal(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    protected function resetForm(): void
    {
        $this->reset(['editingId', 'code', 'name', 'type', 'is_active']);
        $this->type = 'ward';
        $this->is_active = true;
        $this->resetErrorBag();
    }

    public function render()
    {
        $units = Unit::query()
            ->when($this->search, fn ($q) => $q->where(function ($sub) {
                $sub->where('name', 'like', "%{$this->search}%")
                    ->orWhere('code', 'like', "%{$this->search}%");
            }))
            ->orderBy('name')
            ->paginate(12);

        return view('livewire.admin.unit-manager', [
            'units' => $units,
            'typeOptions' => UnitType::options(),
        ]);
    }
}
