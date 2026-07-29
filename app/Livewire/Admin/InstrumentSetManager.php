<?php

namespace App\Livewire\Admin;

use App\Models\InstrumentSet;
use App\Models\Item;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class InstrumentSetManager extends Component
{
    use WithFileUploads, WithPagination;

    #[Url(as: 'q', keep: false)]
    public string $search = '';

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $code = '';

    public string $name = '';

    public string $description = '';

    public bool $is_active = true;

    /** Foto contoh set, memudahkan pengenalan saat perakitan ulang. */
    public $photo;

    public ?string $existingPhoto = null;

    /** @var array<int, array{item_id: string, quantity: int}> Komposisi isi set. */
    public array $setItems = [];

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
        $set = InstrumentSet::with('items')->findOrFail($id);

        $this->editingId = $set->id;
        $this->code = $set->code;
        $this->name = $set->name;
        $this->description = $set->description ?? '';
        $this->is_active = $set->is_active;
        $this->existingPhoto = $set->photo_path;
        $this->photo = null;

        $this->setItems = $set->items
            ->map(fn (Item $item) => [
                'item_id' => (string) $item->id,
                'quantity' => (int) $item->pivot->quantity,
            ])
            ->values()
            ->all();

        $this->resetErrorBag();
        $this->showForm = true;
    }

    public function addRow(): void
    {
        $this->setItems[] = ['item_id' => '', 'quantity' => 1];
    }

    public function removeRow(int $index): void
    {
        unset($this->setItems[$index]);
        $this->setItems = array_values($this->setItems);
    }

    public function save(): void
    {
        $data = $this->validate([
            'code' => ['required', 'string', 'max:32', Rule::unique('instrument_sets', 'code')->ignore($this->editingId)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
            'photo' => ['nullable', 'image', 'max:4096'],
            'setItems' => ['array'],
            'setItems.*.item_id' => ['required', 'exists:items,id'],
            'setItems.*.quantity' => ['required', 'integer', 'min:1', 'max:999'],
        ], [
            'setItems.*.item_id.required' => 'Pilih alat pada baris ini.',
            'setItems.*.quantity.required' => 'Jumlah wajib diisi.',
            'setItems.*.quantity.min' => 'Jumlah minimal 1.',
        ]);

        // Satu jenis alat tidak boleh muncul dua kali dalam satu set.
        $ids = collect($this->setItems)->pluck('item_id');
        if ($ids->count() !== $ids->unique()->count()) {
            $this->addError('setItems', 'Ada jenis alat yang dipilih lebih dari sekali. Gabungkan jumlahnya jadi satu baris.');

            return;
        }

        $attributes = [
            'code' => $data['code'],
            'name' => $data['name'],
            'description' => $data['description'] ?: null,
            'is_active' => $data['is_active'],
        ];

        // Foto lama dipertahankan bila tidak ada unggahan baru.
        if ($this->photo) {
            $attributes['photo_path'] = $this->photo->store('set-photos', 'public');

            if ($this->existingPhoto) {
                Storage::disk('public')->delete($this->existingPhoto);
            }
        }

        $set = InstrumentSet::updateOrCreate(['id' => $this->editingId], $attributes);

        $set->items()->sync(
            collect($this->setItems)
                ->mapWithKeys(fn (array $row) => [(int) $row['item_id'] => ['quantity' => (int) $row['quantity']]])
                ->all()
        );

        $this->showForm = false;
        $this->resetForm();

        session()->flash('status', 'Data set alat berhasil disimpan.');
    }

    public function removePhoto(): void
    {
        if ($this->editingId && $this->existingPhoto) {
            Storage::disk('public')->delete($this->existingPhoto);
            InstrumentSet::whereKey($this->editingId)->update(['photo_path' => null]);
        }

        $this->existingPhoto = null;
        $this->photo = null;
    }

    public function toggleActive(int $id): void
    {
        $set = InstrumentSet::findOrFail($id);
        $set->update(['is_active' => ! $set->is_active]);

        session()->flash('status', "Set \"{$set->name}\" kini ".($set->is_active ? 'aktif' : 'nonaktif').'.');
    }

    #[On('close-modal')]
    public function closeModal(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    protected function resetForm(): void
    {
        $this->reset(['editingId', 'code', 'name', 'description', 'is_active', 'setItems', 'photo', 'existingPhoto']);
        $this->is_active = true;
        $this->setItems = [];
        $this->resetErrorBag();
    }

    public function render()
    {
        $sets = InstrumentSet::query()
            ->withCount('items')
            // Isi set dimuat lengkap agar dropdown "lihat isi" bisa langsung tampil.
            ->with('items')
            ->when($this->search, fn ($q) => $q->where(function ($sub) {
                $sub->where('name', 'like', "%{$this->search}%")
                    ->orWhere('code', 'like', "%{$this->search}%");
            }))
            ->orderBy('name')
            ->paginate(12);

        return view('livewire.admin.instrument-set-manager', [
            'sets' => $sets,
            'itemOptions' => Item::active()->orderBy('code')->get(['id', 'code', 'name']),
        ]);
    }
}
