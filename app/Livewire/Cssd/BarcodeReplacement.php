<?php

namespace App\Livewire\Cssd;

use App\Enums\BatchType;
use App\Enums\ItemBatchStatus;
use App\Models\InstrumentSet;
use App\Models\Item;
use App\Models\ItemBatch;
use App\Services\BarcodeReplacementService;
use App\Services\PublicCodeGenerator;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Mengganti barcode alat yang sudah bersih ("mengubah barcode lama" pada alur).
 *
 * Kegunaan utamanya: beberapa alat lepasan yang sudah bersih dirakit kembali
 * menjadi satu set, lalu diwakili SATU barcode baru disertai foto set terakit.
 * Barcode lama tidak hilang — ditandai diganti dan tetap tertaut untuk audit.
 */
class BarcodeReplacement extends Component
{
    use WithFileUploads;

    public string $code = '';

    /** @var array<int, int> */
    public array $selectedIds = [];

    public string $newType = 'set';

    public string $instrument_set_id = '';

    public string $item_id = '';

    public int $quantity = 1;

    public string $reason = '';

    public $photo;

    public string $feedback = '';

    public string $feedbackType = '';

    /** Menambahkan barcode lama ke daftar — lewat scan atau ketik manual. */
    public function addCode(): void
    {
        $code = PublicCodeGenerator::normalize($this->code);
        $this->code = '';

        if ($code === '') {
            return;
        }

        $batch = ItemBatch::where('public_code', $code)->first();

        if (! $batch) {
            $this->flash('error', "Kode {$code} tidak dikenal.");

            return;
        }

        if (in_array($batch->id, $this->selectedIds, true)) {
            $this->flash('info', "Kode {$code} sudah ada di daftar.");

            return;
        }

        // Penggantian barcode hanya masuk akal setelah alat dinyatakan bersih.
        if ($batch->status !== ItemBatchStatus::CleanlinessCheckPending && $batch->status !== ItemBatchStatus::CleanPendingPack) {
            $this->flash('error', "{$code} berstatus \"{$batch->status->label()}\" — barcode hanya bisa diganti setelah alat lolos cek kebersihan.");

            return;
        }

        $this->selectedIds[] = $batch->id;
        $this->flash('success', "{$code} ditambahkan.");
    }

    public function removeCode(int $id): void
    {
        $this->selectedIds = array_values(array_filter($this->selectedIds, fn (int $v) => $v !== $id));
    }

    public function save(BarcodeReplacementService $service): void
    {
        $this->authorize('advanceStage', ItemBatch::class);

        $rules = [
            'selectedIds' => ['required', 'array', 'min:1'],
            'newType' => ['required', 'in:set,individual'],
            'reason' => ['nullable', 'string', 'max:500'],
            'photo' => ['nullable', 'image', 'max:4096'],
        ];

        if ($this->newType === BatchType::Set->value) {
            $rules['instrument_set_id'] = ['required', 'exists:instrument_sets,id'];
        } else {
            $rules['item_id'] = ['required', 'exists:items,id'];
            $rules['quantity'] = ['required', 'integer', 'min:1', 'max:999'];
        }

        $this->validate($rules, [
            'selectedIds.required' => 'Tambahkan minimal satu barcode lama.',
        ], [
            'instrument_set_id' => 'set alat baru',
            'item_id' => 'alat baru',
        ]);

        $photoPath = $this->photo
            ? $this->photo->store('assembled-sets', 'public')
            : null;

        try {
            $new = $service->replace(
                oldBatchIds: $this->selectedIds,
                staff: auth()->user(),
                newType: BatchType::from($this->newType),
                instrumentSetId: $this->instrument_set_id ? (int) $this->instrument_set_id : null,
                itemId: $this->item_id ? (int) $this->item_id : null,
                quantity: $this->quantity,
                reason: $this->reason ?: null,
                photoPath: $photoPath,
            );
        } catch (InvalidArgumentException $e) {
            if ($photoPath) {
                Storage::disk('public')->delete($photoPath);
            }

            $this->addError('selectedIds', $e->getMessage());

            return;
        }

        $this->reset(['selectedIds', 'instrument_set_id', 'item_id', 'quantity', 'reason', 'photo']);
        $this->quantity = 1;

        session()->flash('status', "Barcode baru {$new->public_code} dibuat. Jangan lupa cetak dan tempel labelnya.");

        $this->redirectRoute('cssd.labels', ['q' => $new->public_code], navigate: true);
    }

    private function flash(string $type, string $message): void
    {
        $this->feedbackType = $type;
        $this->feedback = $message;
        $this->dispatch('toast', type: $type, message: $message);
    }

    public function render()
    {
        return view('livewire.cssd.barcode-replacement', [
            'selected' => ItemBatch::whereIn('id', $this->selectedIds)
                ->with(['instrumentSet', 'item', 'originUnit'])
                ->get(),
            'setOptions' => InstrumentSet::active()->orderBy('name')->get(['id', 'code', 'name']),
            'itemOptions' => Item::active()->orderBy('code')->get(['id', 'code', 'name']),
        ]);
    }
}
