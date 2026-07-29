<?php

namespace App\Livewire\Cssd;

use App\Enums\AssetStatus;
use App\Enums\AssetType;
use App\Models\Asset;
use App\Models\InstrumentSet;
use App\Models\Item;
use App\Services\AssetRegistrationService;
use InvalidArgumentException;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Gudang steril: stok tersedia, ditambah pendaftaran alat baru ke inventaris.
 *
 * Stok ditampilkan dua cara sesuai kesepakatan: set dihitung per unit fisik
 * (karena tiap set punya identitas & isi sendiri), alat lepasan diringkas per jenis.
 */
class StockManager extends Component
{
    use WithPagination;

    #[Url(as: 'q', keep: false)]
    public string $search = '';

    #[Url(as: 'tipe', keep: false)]
    public string $filterType = '';

    #[Url(as: 'status', keep: false)]
    public string $filterStatus = '';

    public bool $showRegister = false;

    public string $newType = 'set';

    public string $instrument_set_id = '';

    public string $item_id = '';

    public int $registerQuantity = 1;

    public function updated(): void
    {
        $this->resetPage();
    }

    public function register(AssetRegistrationService $service): void
    {
        $this->authorize('advanceStage', Asset::class);

        $rules = [
            'newType' => ['required', 'in:set,item'],
            'registerQuantity' => ['required', 'integer', 'min:1', 'max:50'],
        ];

        if ($this->newType === AssetType::Set->value) {
            $rules['instrument_set_id'] = ['required', 'exists:instrument_sets,id'];
        } else {
            $rules['item_id'] = ['required', 'exists:items,id'];
        }

        $this->validate($rules, [], [
            'instrument_set_id' => 'jenis set',
            'item_id' => 'jenis alat',
            'registerQuantity' => 'jumlah',
        ]);

        try {
            for ($i = 0; $i < $this->registerQuantity; $i++) {
                $service->register(
                    type: AssetType::from($this->newType),
                    instrumentSetId: $this->instrument_set_id ? (int) $this->instrument_set_id : null,
                    itemId: $this->item_id ? (int) $this->item_id : null,
                    actor: auth()->user(),
                );
            }
        } catch (InvalidArgumentException $e) {
            $this->addError('newType', $e->getMessage());

            return;
        }

        $count = $this->registerQuantity;
        $this->showRegister = false;
        $this->reset(['instrument_set_id', 'item_id', 'registerQuantity']);
        $this->registerQuantity = 1;

        session()->flash('status', "{$count} aset baru terdaftar. Cetak labelnya di menu Buat Barcode.");
    }

    public function render()
    {
        $assets = Asset::query()
            ->with(['instrumentSet', 'item', 'batch.unit'])
            ->when($this->search, fn ($q) => $q->where(function ($sub) {
                $sub->where('current_code', 'like', '%'.strtoupper($this->search).'%')
                    ->orWhereHas('instrumentSet', fn ($s) => $s->where('name', 'like', "%{$this->search}%"))
                    ->orWhereHas('item', fn ($s) => $s->where('name', 'like', "%{$this->search}%"));
            }))
            ->when($this->filterType, fn ($q) => $q->where('asset_type', $this->filterType))
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->orderByDesc('status_changed_at')
            ->paginate(20);

        return view('livewire.cssd.stock-manager', [
            'assets' => $assets,
            'typeOptions' => AssetType::options(),
            'statusOptions' => AssetStatus::options(),
            'setOptions' => InstrumentSet::active()->orderBy('name')->get(['id', 'code', 'name']),
            'itemOptions' => Item::active()->orderBy('code')->get(['id', 'code', 'name']),

            // Set: dihitung per entitas fisik karena tiap set punya identitas sendiri.
            'availableSets' => Asset::query()
                ->inStock()
                ->where('asset_type', AssetType::Set)
                ->with('instrumentSet')
                ->get()
                ->groupBy('instrument_set_id'),

            // Alat lepasan: diringkas per jenis, sesuai cara unit memesannya.
            'availableItems' => Asset::query()
                ->inStock()
                ->where('asset_type', AssetType::Item)
                ->selectRaw('item_id, count(*) as total')
                ->groupBy('item_id')
                ->with('item')
                ->get(),
        ]);
    }
}
