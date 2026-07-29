<?php

namespace App\Livewire\Cssd;

use App\Models\Asset;
use App\Models\Batch;
use App\Models\Unit;
use App\Services\DocumentNumberGenerator;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Kelola batch, kumpulan alat milik unit yang menetap lintas siklus.
 * Batch inilah yang dipesan ulang unit tiap kali butuh.
 */
class BatchManager extends Component
{
    use WithPagination;

    #[Url(as: 'q', keep: false)]
    public string $search = '';

    #[Url(as: 'unit', keep: false)]
    public string $filterUnit = '';

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $unit_id = '';

    public string $notes = '';

    public bool $is_active = true;

    public function updated(): void
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
        $batch = Batch::findOrFail($id);

        $this->editingId = $batch->id;
        $this->name = $batch->name;
        $this->unit_id = (string) $batch->unit_id;
        $this->notes = $batch->notes ?? '';
        $this->is_active = $batch->is_active;

        $this->resetErrorBag();
        $this->showForm = true;
    }

    public function save(DocumentNumberGenerator $numbers): void
    {
        $this->authorize('advanceStage', Asset::class);

        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'unit_id' => ['required', Rule::exists('units', 'id')],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ], [], ['unit_id' => 'unit pemilik']);

        Batch::updateOrCreate(['id' => $this->editingId], [
            'code' => $this->editingId ? Batch::find($this->editingId)->code : $numbers->batch(),
            'name' => $data['name'],
            'unit_id' => (int) $data['unit_id'],
            'notes' => $data['notes'] ?: null,
            'is_active' => $data['is_active'],
            'created_by_user_id' => $this->editingId ? Batch::find($this->editingId)->created_by_user_id : auth()->id(),
        ]);

        $this->showForm = false;
        $this->resetForm();

        session()->flash('status', 'Batch berhasil disimpan.');
    }

    /** Melepas satu aset dari batch, alat kembali menjadi stok bebas. */
    public function releaseAsset(int $assetId): void
    {
        $this->authorize('advanceStage', Asset::class);

        $asset = Asset::findOrFail($assetId);
        $batchName = $asset->batch?->name;

        $asset->forceFill(['batch_id' => null])->save();

        app(\App\Services\AssetTransitionService::class)->recordNote(
            $asset,
            auth()->user(),
            'Kelola Batch',
            "Dilepas dari batch \"{$batchName}\" ke stok bebas.",
        );

        session()->flash('status', "{$asset->current_code} dilepas ke stok bebas.");
    }

    #[On('close-modal')]
    public function closeModal(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    protected function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'unit_id', 'notes', 'is_active']);
        $this->is_active = true;
        $this->resetErrorBag();
    }

    public function render()
    {
        $batches = Batch::query()
            ->with(['unit', 'assets.instrumentSet', 'assets.item'])
            ->withCount('assets')
            ->orderByDesc('is_active')
            ->when($this->search, fn ($q) => $q->where(function ($sub) {
                $sub->where('name', 'like', "%{$this->search}%")
                    ->orWhere('code', 'like', "%{$this->search}%");
            }))
            ->when($this->filterUnit, fn ($q) => $q->where('unit_id', $this->filterUnit))
            ->orderBy('name')
            ->paginate(10);

        return view('livewire.cssd.batch-manager', [
            'batches' => $batches,
            'unitOptions' => Unit::active()->orderBy('name')->get(['id', 'name']),
            'activeCount' => Batch::active()->count(),
            'inBatchCount' => Asset::whereNotNull('batch_id')->count(),
            'freeStockCount' => Asset::inStock()->count(),
        ]);
    }
}
