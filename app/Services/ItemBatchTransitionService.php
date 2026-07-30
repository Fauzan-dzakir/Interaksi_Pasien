<?php

namespace App\Services;

use App\Enums\ItemBatchStatus;
use App\Enums\ScanInputMethod;
use App\Exceptions\InvalidTransitionException;
use App\Models\ItemBatch;
use App\Models\ItemBatchEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Satu-satunya pintu untuk mengubah status batch alat.
 *
 * Tiga jaminan yang diberikan kelas ini:
 *  1. Setiap perubahan status SELALU meninggalkan jejak audit (pelaku + waktu + metode input).
 *  2. Transisi yang tidak sah ditolak tegas — bukan didiamkan.
 *  3. Dua petugas yang men-scan alat yang sama bersamaan tidak bisa saling menimpa,
 *     karena baris dikunci (lockForUpdate) di dalam satu transaksi.
 */
class ItemBatchTransitionService
{
    /**
     * Memindahkan batch ke tahap berikutnya.
     *
     * Bersifat idempotent: scan ganda ke status yang sama (mis. scanner HID
     * terpicu dua kali) diabaikan tanpa error dan tanpa jejak ganda.
     *
     * @return bool true bila status benar-benar berubah, false bila scan berulang.
     *
     * @throws InvalidTransitionException
     */
    public function transition(
        ItemBatch $batch,
        ItemBatchStatus $target,
        ?User $actor,
        ScanInputMethod $inputMethod = ScanInputMethod::Manual,
        ?string $station = null,
        ?string $note = null,
        array $metadata = [],
    ): bool {
        return DB::transaction(function () use ($batch, $target, $actor, $inputMethod, $station, $note, $metadata) {
            /** @var ItemBatch $locked */
            $locked = ItemBatch::whereKey($batch->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->status === $target) {
                return false;
            }

            if (! $locked->status->canTransitionTo($target)) {
                throw new InvalidTransitionException($locked->status, $target, $locked->public_code);
            }

            $this->apply($locked, $target, $actor, $inputMethod, $station, $note, $metadata, false);

            $batch->refresh();

            return true;
        });
    }

    /**
     * Koreksi manual oleh Admin — boleh melompati peta transisi (mis. menandai
     * alat hilang, atau membetulkan salah scan petugas), tetapi alasannya WAJIB
     * diisi dan tetap tercatat sebagai jejak audit yang ditandai override.
     */
    public function adminOverride(
        ItemBatch $batch,
        ItemBatchStatus $target,
        User $admin,
        string $reason,
    ): bool {
        if (trim($reason) === '') {
            throw new InvalidArgumentException('Alasan koreksi wajib diisi.');
        }

        return DB::transaction(function () use ($batch, $target, $admin, $reason) {
            /** @var ItemBatch $locked */
            $locked = ItemBatch::whereKey($batch->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->status === $target) {
                return false;
            }

            $this->apply($locked, $target, $admin, ScanInputMethod::Manual, 'Koreksi Admin', $reason, [], true);

            $batch->refresh();

            return true;
        });
    }

    /**
     * Treatment untuk alat steril yang masa kedaluwarsanya sudah lewat sebelum
     * sempat diserahkan: alat tidak boleh didistribusikan, jadi dikirim balik
     * ke tahap Pengemasan agar dikemas dan disterilkan ulang. Dibuka untuk
     * petugas CSSD (bukan cuma Admin) karena ini bagian dari alur normal
     * menjaga keamanan pasien, bukan koreksi kesalahan.
     */
    public function flagExpiredForResterilization(ItemBatch $batch, User $actor): bool
    {
        return DB::transaction(function () use ($batch, $actor) {
            /** @var ItemBatch $locked */
            $locked = ItemBatch::whereKey($batch->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->status !== ItemBatchStatus::InStorage) {
                throw new InvalidArgumentException('Hanya alat di gudang steril yang bisa ditandai kedaluwarsa.');
            }

            if (! $locked->sterilization_expired_at || $locked->sterilization_expired_at->isFuture()) {
                throw new InvalidArgumentException('Alat ini belum melewati masa kedaluwarsa sterilisasi.');
            }

            $this->apply(
                $locked,
                ItemBatchStatus::Packaging,
                $actor,
                ScanInputMethod::Manual,
                'Kedaluwarsa Steril',
                'Masa sterilisasi lewat sebelum diserahkan — dikemas & disterilkan ulang.',
                [],
                false,
            );

            $batch->refresh();

            return true;
        });
    }

    /**
     * Mencatat status awal saat batch pertama kali dibuat, supaya jejak audit
     * lengkap sejak detik pertama alat masuk sistem.
     */
    public function recordCreation(
        ItemBatch $batch,
        ?User $actor,
        ?string $note = null,
    ): void {
        ItemBatchEvent::create([
            'item_batch_id' => $batch->id,
            'delivery_order_id' => $batch->current_delivery_order_id,
            'from_status' => null,
            'to_status' => $batch->status,
            'actor_user_id' => $actor?->id,
            'input_method' => ScanInputMethod::Manual,
            'station_context' => 'Pendataan CSSD',
            'note' => $note,
            'is_admin_override' => false,
            'occurred_at' => now(),
        ]);
    }

    private function apply(
        ItemBatch $batch,
        ItemBatchStatus $target,
        ?User $actor,
        ScanInputMethod $inputMethod,
        ?string $station,
        ?string $note,
        array $metadata,
        bool $isOverride,
    ): void {
        $from = $batch->status;

        $updates = [
            'status' => $target,
            'status_changed_at' => now(),
        ];

        // Jika batch baru masuk Gudang Steril (lolos cek steril), atur expired date
        if ($target === ItemBatchStatus::InStorage && $from !== ItemBatchStatus::InStorage) {
            // Ambil metode sterilisasi dari item (jika individual) atau biarkan default/autoclave
            $method = null;
            if ($batch->item_id && $batch->item) {
                $method = $batch->item->sterilization_method;
            }
            
            // Jika tidak ada di item, gunakan metode terakhir atau default Autoclave
            $method = $method ?? $batch->sterilization_method ?? \App\Enums\SterilizationMethod::Autoclave;
            
            $updates['sterilization_method'] = $method;
            $updates['sterilization_expired_at'] = now()->addMonths($method->expirationMonths());
        }

        $batch->forceFill($updates)->save();

        ItemBatchEvent::create([
            'item_batch_id' => $batch->id,
            'delivery_order_id' => $batch->current_delivery_order_id,
            'from_status' => $from,
            'to_status' => $target,
            'actor_user_id' => $actor?->id,
            'input_method' => $inputMethod,
            'station_context' => $station,
            'note' => $note,
            'is_admin_override' => $isOverride,
            'metadata' => $metadata ?: null,
            'occurred_at' => now(),
        ]);
    }
}
