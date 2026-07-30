<?php

namespace App\Livewire\Shared;

use App\Enums\BatchQcStage;
use App\Enums\ItemBatchStatus;
use App\Exceptions\InvalidTransitionException;
use App\Models\ItemBatch;
use App\Models\ItemBatchStageCheck;
use App\Models\User;
use App\Notifications\ItemBatchIssueReported;
use App\Services\DeliveryOrderService;
use App\Services\ItemBatchTransitionService;
use App\Services\QrCodeService;
use Illuminate\Support\Facades\Notification;
use InvalidArgumentException;
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

    /** @var array<int, array{key: string, label: string, is_present: bool, note: string}> */
    public array $checklist = [];

    public string $checklistFailTarget = '';

    public function mount(ItemBatch $batch): void
    {
        $this->authorize('view', $batch);
        $this->batch = $batch;

        $this->resetChecklist();
    }

    private function resetChecklist(): void
    {
        $stage = BatchQcStage::forStatus($this->batch->status);

        $this->checklist = $stage
            ? collect($stage->items())->map(fn (array $item) => [
                'key' => $item['key'],
                'label' => $item['label'],
                'is_present' => true,
                'note' => '',
            ])->all()
            : [];

        $this->checklistFailTarget = $stage && count($stage->failTargets()) > 0
            ? $stage->failTargets()[0]->value
            : '';
    }

    /**
     * Checklist QC opsional di halaman detail alat — dokumentasi tambahan,
     * BUKAN pengganti Stasiun Scan. Kalau semua item "sesuai", alat otomatis
     * dipindah ke tahap berikutnya. Kalau ada yang "tidak sesuai" (wajib diberi
     * catatan), alat dikembalikan ke tahap sebelumnya yang relevan — atau kalau
     * tidak ada tahap mundur yang relevan di sistem ini, alat tetap di tahap
     * sekarang dan checklist ini jadi catatan untuk ditindaklanjuti manual.
     */
    public function submitChecklist(ItemBatchTransitionService $transitions, DeliveryOrderService $orders): void
    {
        $this->authorize('advanceStage', ItemBatch::class);

        $stage = BatchQcStage::forStatus($this->batch->status);

        if (! $stage) {
            return;
        }

        $this->validate([
            'checklist.*.note' => ['nullable', 'string', 'max:255'],
        ]);

        $failedItems = collect($this->checklist)->reject(fn (array $row) => $row['is_present']);

        if ($failedItems->isNotEmpty() && $failedItems->contains(fn (array $row) => trim($row['note']) === '')) {
            $this->addError('checklist', 'Item yang ditandai "tidak sesuai" wajib diberi catatan.');

            return;
        }

        $failTargets = $stage->failTargets();

        if ($failedItems->isNotEmpty() && count($failTargets) > 1) {
            $this->validate([
                'checklistFailTarget' => ['required', 'in:'.collect($failTargets)->map(fn (ItemBatchStatus $s) => $s->value)->implode(',')],
            ], [], ['checklistFailTarget' => 'tahap tujuan']);
        }

        $now = now();
        $user = auth()->user();

        foreach ($this->checklist as $row) {
            ItemBatchStageCheck::create([
                'item_batch_id' => $this->batch->id,
                'stage' => $stage->value,
                'item_key' => $row['key'],
                'item_label' => $row['label'],
                'is_present' => $row['is_present'],
                'note' => $row['note'] !== '' ? $row['note'] : null,
                'recorded_by_user_id' => $user->id,
                'recorded_at' => $now,
            ]);
        }

        if ($failedItems->isEmpty()) {
            $summary = "Checklist QC {$stage->label()}: semua item sesuai.";
            $target = $stage->passTarget();
        } elseif (count($failTargets) > 0) {
            $names = $failedItems->pluck('label')->implode(', ');
            $summary = "Checklist QC {$stage->label()}: tidak sesuai pada {$names}.";
            $target = count($failTargets) > 1
                ? ItemBatchStatus::from($this->checklistFailTarget)
                : $failTargets[0];
        } else {
            // Tidak ada tahap mundur yang relevan — status tidak berubah, hanya dicatat.
            $this->notifyOriginUnitOfFailedChecklist($stage->label(), $failedItems->pluck('label')->implode(', '));

            session()->flash('status', 'Checklist tersimpan. Ada item tidak sesuai — tahap tidak berubah, tindak lanjuti secara manual.');
            $this->resetChecklist();

            return;
        }

        try {
            $transitions->transition(
                $this->batch,
                $target,
                $user,
                \App\Enums\ScanInputMethod::Manual,
                'Checklist QC — '.$stage->label(),
                $summary,
            );
        } catch (InvalidTransitionException $e) {
            $this->addError('checklist', $e->getMessage());

            return;
        }

        if ($this->batch->currentDeliveryOrder) {
            $orders->syncStatus($this->batch->currentDeliveryOrder, $user);
        }

        if ($failedItems->isNotEmpty()) {
            $this->notifyOriginUnitOfFailedChecklist($stage->label(), $failedItems->pluck('label')->implode(', '));
        }

        $this->batch->refresh();
        $this->resetChecklist();

        session()->flash('status', $failedItems->isEmpty()
            ? "Checklist sesuai — alat dipindah ke \"{$target->label()}\"."
            : "Checklist dicatat — alat dikembalikan ke \"{$target->label()}\" karena ada item tidak sesuai.");
    }

    private function notifyOriginUnitOfFailedChecklist(string $stageLabel, string $failedNames): void
    {
        $this->notifyOriginUnit(
            'Alat tidak lulus uji '.$stageLabel,
            "{$this->batch->displayName()} ({$this->batch->public_code}) tidak lulus uji {$stageLabel} pada: {$failedNames}. Alat dikembalikan untuk diproses ulang.",
        );
    }

    /** Beri tahu unit pemilik alat — dipakai untuk kejadian yang perlu diketahui unit (hilang/rusak/gagal uji). */
    private function notifyOriginUnit(string $title, string $message): void
    {
        if (! $this->batch->origin_unit_id) {
            return;
        }

        $recipients = User::active()->where('unit_id', $this->batch->origin_unit_id)->get();

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new ItemBatchIssueReported($this->batch, $title, $message));
        }
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
        $this->resetChecklist();

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
    public function applyOverride(ItemBatchTransitionService $transitions, DeliveryOrderService $orders): void
    {
        $this->authorize('override', ItemBatch::class);

        $this->validate([
            'overrideStatus' => ['required'],
            'overrideReason' => ['required', 'string', 'min:5', 'max:500'],
        ], [], [
            'overrideStatus' => 'status tujuan',
            'overrideReason' => 'alasan koreksi',
        ]);

        $target = ItemBatchStatus::from($this->overrideStatus);

        try {
            $transitions->adminOverride(
                $this->batch,
                $target,
                auth()->user(),
                $this->overrideReason,
            );
        } catch (InvalidArgumentException $e) {
            $this->addError('overrideReason', $e->getMessage());

            return;
        }

        // Tanpa ini, status order tetap "Sedang Diproses"/"Selesai" seolah tidak
        // terjadi apa-apa — order harus ikut menandakan ada alat bermasalah.
        if ($this->batch->currentDeliveryOrder) {
            $orders->syncStatus($this->batch->currentDeliveryOrder, auth()->user());
        }

        if (in_array($target, [ItemBatchStatus::Lost, ItemBatchStatus::Retired], true)) {
            $this->notifyOriginUnit(
                $target === ItemBatchStatus::Lost ? 'Alat dinyatakan hilang' : 'Alat ditandai tidak dipakai lagi',
                "{$this->batch->displayName()} ({$this->batch->public_code}) ditandai \"{$target->label()}\". Alasan: {$this->overrideReason}",
            );
        }

        $this->showOverride = false;
        $this->reset(['overrideStatus', 'overrideReason']);
        $this->batch->refresh();
        $this->resetChecklist();

        session()->flash('status', 'Koreksi tersimpan dan tercatat pada jejak audit.');
    }

    public function render(QrCodeService $qr)
    {
        $this->batch->load([
            'instrumentSet', 'item', 'originUnit', 'currentDeliveryOrder',
            'events.actor', 'supersededBy.newBatch', 'supersedes.oldBatch',
            'stageChecks.recordedBy',
        ]);

        $user = auth()->user();

        return view('livewire.shared.batch-show', [
            'qrSvg' => $qr->svg($this->batch->public_code, 150),
            'lineage' => $this->batch->lineage(),
            'canAdvance' => $user->can('advanceStage', ItemBatch::class),
            'canOverride' => $user->can('override', ItemBatch::class),
            'nextOptions' => $this->batch->status->allowedNext(),
            'overrideOptions' => ItemBatchStatus::cases(),
            'qcStage' => BatchQcStage::forStatus($this->batch->status),
            'checkedHistoryGroups' => $this->batch->stageChecks->groupBy('recorded_at'),
        ]);
    }
}
