<?php

namespace App\Livewire\Admin;

use App\Models\InstrumentSet;
use App\Models\Item;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class InstrumentSetManager extends Component
{
    use WithPagination;

    #[Url(as: 'q', keep: false)]
    public string $search = '';

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $code = '';

    public string $name = '';

    public string $description = '';

    public bool $is_active = true;

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

        $set = InstrumentSet::updateOrCreate(['id' => $this->editingId], [
            'code' => $data['code'],
            'name' => $data['name'],
            'description' => $data['description'] ?: null,
            'is_active' => $data['is_active'],
        ]);

        $set->items()->sync(
            collect($this->setItems)
                ->mapWithKeys(fn (array $row) => [(int) $row['item_id'] => ['quantity' => (int) $row['quantity']]])
                ->all()
        );

        $this->showForm = false;
        $this->resetForm();

        session()->flash('status', 'Data set alat berhasil disimpan.');
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
        $this->reset(['editingId', 'code', 'name', 'description', 'is_active', 'setItems']);
        $this->is_active = true;
        $this->setItems = [];
        $this->resetErrorBag();
    }

    public function render()
    {
        $sets = InstrumentSet::query()
            ->withCount('items')
            ->with('items:id')
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
