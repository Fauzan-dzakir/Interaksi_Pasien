<?php

namespace App\Livewire\Unit;

use App\Enums\BatchType;
use App\Enums\ItemBatchStatus;
use App\Enums\ScanInputMethod;
use App\Exceptions\InvalidTransitionException;
use App\Models\ItemBatch;
use App\Models\ItemBatchUsageMark;
use App\Services\ItemBatchTransitionService;
use App\Services\PublicCodeGenerator;
use Livewire\Component;

/**
 * Pendataan alat real-time di unit — pengganti daftar "Alat di Unit Ini" yang
 * dulu menempel di form Buat Order. Di sini alat yang sudah diambil dari CSSD
 * tapi BELUM dipakai (status "picked_up") ditandai pemakaiannya lewat scan atau
 * checklist isi set. Begitu ditandai dipakai, alat lepas dari daftar real-time
 * ini (pindah ke "in_use") dan baru muncul lagi di form Buat Order sebagai daftar
 * alat kotor yang siap dikirim balik (lihat OrderCreate — sekarang cuma tampilan,
 * tidak bisa diubah dari sana lagi).
 *
 * Barcode yang sama TIDAK pernah dipakai ulang untuk siklus kotor berikutnya —
 * begitu alat ini benar-benar dikirim balik, CSSD mendata ulang dan barcode baru
 * otomatis dibuat. Riwayat lengkap barcode lama ini tetap permanen di jejak audit.
 */
class UnitInventory extends Component
{
    public string $code = '';

    public string $feedback = '';

    public string $feedbackType = '';

    /** Scan/ketik kode label untuk menandai SATU alat/set langsung "sedang dipakai". */
    public function scanUsage(ItemBatchTransitionService $transitions): void
    {
        $code = PublicCodeGenerator::normalize($this->code);
        $this->code = '';

        if ($code === '') {
            return;
        }

        $unit = auth()->user()->unit;

        $batch = ItemBatch::where('public_code', $code)
            ->where('origin_unit_id', $unit?->id)
            ->whereIn('status', [ItemBatchStatus::PickedUp, ItemBatchStatus::InUse])
            ->with('instrumentSet.items')
            ->first();

        if (! $batch) {
            $this->flash('error', "Kode {$code} tidak ditemukan di antara alat unit Anda yang belum dikirim balik ke CSSD.");

            return;
        }

        if ($batch->status === ItemBatchStatus::InUse) {
            $this->flash('info', "{$batch->displayName()} ({$code}) sudah ditandai dipakai sebelumnya.");

            return;
        }

        // Untuk set: scan barcode luarnya berarti seluruh isi set dipakai (tray dibuka).
        // Kalau cuma sebagian isi set yang dipakai, tandai lewat checklist di tabel, bukan scan ini.
        if ($batch->batch_type === BatchType::Set) {
            $now = now();
            foreach ($batch->instrumentSet->items as $setItem) {
                ItemBatchUsageMark::updateOrCreate(
                    ['item_batch_id' => $batch->id, 'item_id' => $setItem->id],
                    ['is_used' => true, 'marked_by_user_id' => auth()->id(), 'marked_at' => $now],
                );
            }
        }

        try {
            $transitions->transition(
                $batch,
                ItemBatchStatus::InUse,
                auth()->user(),
                ScanInputMethod::HidScanner,
                'Pendataan Alat di Unit — scan pemakaian',
            );
        } catch (InvalidTransitionException $e) {
            $this->flash('error', $e->getMessage());

            return;
        }

        $this->flash('success', "{$batch->displayName()} ({$code}) ditandai sedang dipakai.");
    }

    /**
     * Alat "Per Barang" — satu QR satu unit fisik, jadi tinggal dibalik antara
     * "Sedang Dipakai" <-> "Sudah Diambil" (belum dipakai).
     */
    public function toggleIndividualUsage(int $batchId, ItemBatchTransitionService $transitions): void
    {
        $batch = ItemBatch::where('id', $batchId)
            ->where('origin_unit_id', auth()->user()->unit_id)
            ->firstOrFail();

        $target = $batch->status === ItemBatchStatus::InUse
            ? ItemBatchStatus::PickedUp
            : ItemBatchStatus::InUse;

        try {
            $transitions->transition(
                $batch,
                $target,
                auth()->user(),
                ScanInputMethod::Manual,
                'Pendataan Alat di Unit — tandai pemakaian',
            );
        } catch (InvalidTransitionException $e) {
            $this->addError('batches', $e->getMessage());
        }
    }

    /**
     * Alat "Per Set" — satu QR mewakili satu set utuh, tapi unit perlu menandai
     * isinya satu per satu (mis. gunting dipakai, needle holder tidak).
     *
     * SENGAJA tidak langsung memindahkan status batch di sini — kalau langsung
     * dipindah begitu SATU item dicentang, set itu langsung hilang dari daftar
     * real-time ini (render() cuma tampilkan status PickedUp), padahal petugas
     * belum selesai mencentang sisa isi set yang lain. Perpindahan status baru
     * terjadi lewat confirmSetUsage() setelah petugas menekan tombol konfirmasi.
     */
    public function toggleSetItemMark(int $batchId, int $itemId): void
    {
        $batch = ItemBatch::where('id', $batchId)
            ->where('origin_unit_id', auth()->user()->unit_id)
            ->firstOrFail();

        $mark = ItemBatchUsageMark::firstOrNew([
            'item_batch_id' => $batch->id,
            'item_id' => $itemId,
        ]);

        $mark->is_used = ! $mark->is_used;
        $mark->marked_by_user_id = auth()->id();
        $mark->marked_at = now();
        $mark->save();
    }

    /**
     * Tombol konfirmasi per set — dipencet setelah petugas selesai mencentang
     * isi set yang dipakai (boleh satu atau beberapa sekaligus). Di sinilah
     * status batch baru benar-benar pindah ke "Sedang Dipakai".
     */
    public function confirmSetUsage(int $batchId, ItemBatchTransitionService $transitions): void
    {
        $batch = ItemBatch::where('id', $batchId)
            ->where('origin_unit_id', auth()->user()->unit_id)
            ->firstOrFail();

        $anyUsed = ItemBatchUsageMark::where('item_batch_id', $batch->id)->where('is_used', true)->exists();

        if (! $anyUsed) {
            $this->addError('batches', 'Centang minimal satu alat dalam set ini sebelum konfirmasi.');

            return;
        }

        try {
            $transitions->transition(
                $batch,
                ItemBatchStatus::InUse,
                auth()->user(),
                ScanInputMethod::Manual,
                'Pendataan Alat di Unit — konfirmasi pemakaian isi set',
            );
        } catch (InvalidTransitionException $e) {
            $this->addError('batches', $e->getMessage());

            return;
        }

        $this->flash('success', "{$batch->displayName()} ({$batch->public_code}) ditandai sedang dipakai.");
    }

    private function flash(string $type, string $message): void
    {
        $this->feedbackType = $type;
        $this->feedback = $message;
        $this->dispatch('toast', type: $type, message: $message);
    }

    public function render()
    {
        $unit = auth()->user()->unit;

        return view('livewire.unit.unit-inventory', [
            'batches' => $unit
                ? ItemBatch::where('origin_unit_id', $unit->id)
                    ->where('status', ItemBatchStatus::PickedUp)
                    ->with(['instrumentSet.items', 'item', 'usageMarks'])
                    ->orderBy('status_changed_at')
                    ->get()
                : collect(),
        ]);
    }
}
