<?php

namespace App\Livewire\Shared;

use App\Enums\ItemBatchStatus;
use App\Exceptions\InvalidTransitionException;
use App\Models\ItemBatch;
use App\Services\DeliveryOrderService;
use App\Services\ItemBatchTransitionService;
use App\Services\QrCodeService;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Halaman riwayat satu alat — inti pembuktian saat audit kehilangan:
 * menampilkan setiap perpindahan tahap lengkap dengan pelaku, jam, dan metode input,
 * termasuk rantai penggantian barcode lintas siklus.
 */
class BatchShow extends Component
{
    public ItemBatch $batch;

    public string $overrideStatus = '';

    public string $overrideReason = '';

    public bool $showOverride = false;

    use WithFileUploads;

    public $sterilizationPhotos = [];

    public function mount(ItemBatch $batch): void
    {
        $this->authorize('view', $batch);
        $this->batch = $batch;
    }

    /** Jalur kegagalan QC yang butuh penilaian petugas CSSD. */
    public function moveTo(string $status, ItemBatchTransitionService $transitions, DeliveryOrderService $orders): void
    {
        $this->authorize('advanceStage', ItemBatch::class);

        $target = ItemBatchStatus::from($status);

        try {
            $transitions->transition(
                $this->batch,
                $target,
                auth()->user(),
                \App\Enums\ScanInputMethod::Manual,
                'Tindakan dari halaman detail',
            );
        } catch (InvalidTransitionException $e) {
            $this->addError('action', $e->getMessage());

            return;
        }

        if ($this->batch->currentDeliveryOrder) {
            $orders->syncStatus($this->batch->currentDeliveryOrder, auth()->user());
        }

        $this->batch->refresh();

        session()->flash('status', "Alat dipindah ke \"{$target->label()}\".");
    }

    public function uploadSterilizationPhotos(): void
    {
        $this->authorize('advanceStage', ItemBatch::class);
        $this->validate([
            'sterilizationPhotos.*' => ['image', 'max:5120']
        ]);

        $existing = $this->batch->sterilization_photos ? json_decode($this->batch->sterilization_photos, true) : [];
        
        foreach ($this->sterilizationPhotos as $photo) {
            $existing[] = $photo->store('sterilization', 'public');
        }

        $this->batch->update([
            'sterilization_photos' => json_encode($existing)
        ]);

        $this->reset('sterilizationPhotos');
        session()->flash('status', 'Foto dokumentasi sterilisasi berhasil diunggah.');
    }

    /** Koreksi Admin — boleh melompati alur, tapi alasannya wajib dan tercatat. */
    public function applyOverride(ItemBatchTransitionService $transitions): void
    {
        $this->authorize('override', ItemBatch::class);

        $this->validate([
            'overrideStatus' => ['required'],
            'overrideReason' => ['required', 'string', 'min:5', 'max:500'],
        ], [], [
            'overrideStatus' => 'status tujuan',
            'overrideReason' => 'alasan koreksi',
        ]);

        try {
            $transitions->adminOverride(
                $this->batch,
                ItemBatchStatus::from($this->overrideStatus),
                auth()->user(),
                $this->overrideReason,
            );
        } catch (InvalidArgumentException $e) {
            $this->addError('overrideReason', $e->getMessage());

            return;
        }

        $this->showOverride = false;
        $this->reset(['overrideStatus', 'overrideReason']);
        $this->batch->refresh();

        session()->flash('status', 'Koreksi tersimpan dan tercatat pada jejak audit.');
    }

    public function render(QrCodeService $qr)
    {
        $this->batch->load([
            'instrumentSet', 'item', 'originUnit', 'currentDeliveryOrder',
            'events.actor', 'supersededBy.newBatch', 'supersedes.oldBatch',
        ]);

        $user = auth()->user();

        return view('livewire.shared.batch-show', [
            'qrSvg' => $qr->svg($this->batch->public_code, 150),
            'lineage' => $this->batch->lineage(),
            'canAdvance' => $user->can('advanceStage', ItemBatch::class),
            'canOverride' => $user->can('override', ItemBatch::class),
            'nextOptions' => $this->batch->status->allowedNext(),
            'overrideOptions' => ItemBatchStatus::cases(),
        ]);
    }
}
