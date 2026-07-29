<?php

namespace App\Livewire\Admin;

use App\Enums\MaterialSensitivity;
use App\Models\Item;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class ItemManager extends Component
{
    use WithFileUploads, WithPagination;

    #[Url(as: 'q', keep: false)]
    public string $search = '';

    #[Url(as: 'sens', keep: false)]
    public string $filterSensitivity = '';

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $code = '';

    public string $name = '';

    public string $category = '';

    public string $material_sensitivity = 'heat_water_resistant';

    public string $notes = '';

    public bool $is_active = true;

    /** Foto contoh alat, membantu petugas mengenali alat secara visual. */
    public $photo;

    public ?string $existingPhoto = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterSensitivity(): void
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
        $item = Item::findOrFail($id);

        $this->editingId = $item->id;
        $this->code = $item->code;
        $this->name = $item->name;
        $this->category = $item->category ?? '';
        $this->material_sensitivity = $item->material_sensitivity->value;
        $this->notes = $item->notes ?? '';
        $this->is_active = $item->is_active;
        $this->existingPhoto = $item->photo_path;
        $this->photo = null;

        $this->resetErrorBag();
        $this->showForm = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'code' => ['required', 'string', 'max:32', Rule::unique('items', 'code')->ignore($this->editingId)],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'material_sensitivity' => ['required', Rule::enum(MaterialSensitivity::class)],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
            'photo' => ['nullable', 'image', 'max:4096'],
        ], [], ['photo' => 'foto alat']);

        $data['category'] = $data['category'] ?: null;
        $data['notes'] = $data['notes'] ?: null;

        // Foto lama dipertahankan bila tidak ada unggahan baru.
        if ($this->photo) {
            $data['photo_path'] = $this->photo->store('item-photos', 'public');

            if ($this->existingPhoto) {
                Storage::disk('public')->delete($this->existingPhoto);
            }
        } else {
            unset($data['photo']);
        }

        unset($data['photo']);

        Item::updateOrCreate(['id' => $this->editingId], $data);

        $this->showForm = false;
        $this->resetForm();

        session()->flash('status', 'Data alat berhasil disimpan.');
    }

    public function removePhoto(): void
    {
        if ($this->editingId && $this->existingPhoto) {
            Storage::disk('public')->delete($this->existingPhoto);
            Item::whereKey($this->editingId)->update(['photo_path' => null]);
        }

        $this->existingPhoto = null;
        $this->photo = null;
    }

    /** Katalog alat hanya dinonaktifkan, tidak dihapus, riwayat lama tetap terbaca. */
    public function toggleActive(int $id): void
    {
        $item = Item::findOrFail($id);
        $item->update(['is_active' => ! $item->is_active]);

        session()->flash('status', "Alat \"{$item->name}\" kini ".($item->is_active ? 'aktif' : 'nonaktif').'.');
    }

    #[On('close-modal')]
    public function closeModal(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    protected function resetForm(): void
    {
        $this->reset(['editingId', 'code', 'name', 'category', 'material_sensitivity', 'notes', 'is_active', 'photo', 'existingPhoto']);
        $this->material_sensitivity = 'heat_water_resistant';
        $this->is_active = true;
        $this->resetErrorBag();
    }

    public function render()
    {
        $items = Item::query()
            ->when($this->search, fn ($q) => $q->where(function ($sub) {
                $sub->where('name', 'like', "%{$this->search}%")
                    ->orWhere('code', 'like', "%{$this->search}%")
                    ->orWhere('category', 'like', "%{$this->search}%");
            }))
            ->when($this->filterSensitivity, fn ($q) => $q->where('material_sensitivity', $this->filterSensitivity))
            ->orderBy('code')
            ->paginate(12);

        return view('livewire.admin.item-manager', [
            'items' => $items,
            'sensitivityOptions' => MaterialSensitivity::options(),
            'selectedSensitivity' => $this->material_sensitivity
                ? MaterialSensitivity::tryFrom($this->material_sensitivity)
                : null,
        ]);
    }
}
