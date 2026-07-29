<?php

namespace App\Livewire\Cssd;

use App\Enums\AssetStatus;
use App\Enums\BiologicalIndicatorResult;
use App\Enums\SterilizationMethod;
use App\Models\Asset;
use App\Models\Batch;
use App\Models\SterilizationRecord;
use App\Models\Unit;
use App\Services\SterilizationService;
use InvalidArgumentException;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Laporan proses sterilisasi, pengganti digital formulir kertas CSSD.
 *
 * Petugas hanya mengisi hal yang memang tidak terekam otomatis (metode dan
 * hasil indikator biologi). Tahapan, jam, dan nama petugas diambil dari jejak
 * audit saat PDF dicetak, di situlah keunggulannya dibanding formulir kertas.
 */
class SterilizationReports extends Component
{
    use WithPagination;

    public bool $showForm = false;

    public string $method = 'steam';

    public string $unitId = '';

    public string $batchId = '';

    public array $selectedAssets = [];

    public string $notes = '';

    public ?int $editingResultId = null;

    public string $biResult = 'pending';

    public string $biNotes = '';

    public function create(): void
    {
        $this->reset(['selectedAssets', 'notes', 'batchId', 'unitId']);
        $this->method = 'steam';
        $this->showForm = true;
    }

    public function save(SterilizationService $service): void
    {
        $this->authorize('advanceStage', Asset::class);

        $this->validate([
            'method' => ['required'],
            'selectedAssets' => ['required', 'array', 'min:1'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'selectedAssets.required' => 'Pilih minimal satu alat untuk dimasukkan ke laporan.',
        ]);

        try {
            $record = $service->createRecord(
                staff: auth()->user(),
                method: SterilizationMethod::from($this->method),
                assetIds: array_map('intval', $this->selectedAssets),
                batchId: $this->batchId ? (int) $this->batchId : null,
                unitId: $this->unitId ? (int) $this->unitId : null,
                notes: $this->notes ?: null,
            );
        } catch (InvalidArgumentException $e) {
            $this->addError('selectedAssets', $e->getMessage());

            return;
        }

        $this->showForm = false;
        $this->reset(['selectedAssets', 'notes', 'batchId', 'unitId']);

        session()->flash('status', "Laporan {$record->record_number} dibuat. Cetak PDF-nya bila sudah diperlukan.");
    }

    public function startResult(int $id): void
    {
        $record = SterilizationRecord::findOrFail($id);

        $this->editingResultId = $id;
        $this->biResult = $record->biological_indicator_result->value;
        $this->biNotes = $record->notes ?? '';
        $this->resetErrorBag();
    }

    /** Mencatat hasil uji indikator biologi, penentu muatan boleh dipakai atau tidak. */
    public function saveResult(): void
    {
        $this->authorize('advanceStage', Asset::class);

        $this->validate([
            'biResult' => ['required', 'in:pending,baik,tidak_baik'],
            'biNotes' => ['nullable', 'string', 'max:1000'],
        ], [], ['biResult' => 'hasil indikator biologi']);

        $record = SterilizationRecord::findOrFail($this->editingResultId);
        $result = BiologicalIndicatorResult::from($this->biResult);

        $record->update([
            'biological_indicator_result' => $result,
            'bi_read_at' => $result === BiologicalIndicatorResult::Pending ? null : now(),
            'bi_read_by_user_id' => $result === BiologicalIndicatorResult::Pending ? null : auth()->id(),
            'notes' => $this->biNotes ?: null,
        ]);

        $this->editingResultId = null;

        session()->flash('status', $result === BiologicalIndicatorResult::TidakBaik
            ? 'Hasil TIDAK BAIK tercatat. Muatan ini tidak boleh dipakai ke pasien, segera tindak lanjuti.'
            : 'Hasil indikator biologi tersimpan.');
    }

    public function render()
    {
        return view('livewire.cssd.sterilization-reports', [
            'records' => SterilizationRecord::query()
                ->with(['batch', 'unit', 'createdBy', 'biReadBy'])
                ->withCount('assets')
                ->latest()
                ->paginate(12),
            'methodOptions' => SterilizationMethod::options(),
            'resultOptions' => BiologicalIndicatorResult::options(),
            'unitOptions' => Unit::active()->orderBy('name')->get(['id', 'name']),
            'batchOptions' => Batch::active()->with('unit')->orderBy('name')->get(),

            // Kandidat: alat yang sedang/baru selesai disterilkan.
            'candidates' => Asset::query()
                ->whereIn('status', [AssetStatus::Sterilizing, AssetStatus::Available, AssetStatus::Packed])
                ->when($this->batchId, fn ($q) => $q->where('batch_id', $this->batchId))
                ->with(['instrumentSet', 'item', 'batch'])
                ->orderByDesc('status_changed_at')
                ->limit(60)
                ->get(),
        ]);
    }
}
