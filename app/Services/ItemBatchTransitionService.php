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

        $batch->forceFill([
            'status' => $target,
            'status_changed_at' => now(),
        ])->save();

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
