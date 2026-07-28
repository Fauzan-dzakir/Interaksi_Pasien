<?php

namespace App\Services;

use App\Enums\BatchType;
use App\Enums\ItemBatchStatus;
use App\Enums\ScanInputMethod;
use App\Models\ItemBatch;
use App\Models\ItemBatchEvent;
use App\Models\ItemBatchSupersession;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Penggantian barcode setelah alat dinyatakan bersih ("mengubah barcode lama"
 * pada alur). Dipakai terutama saat beberapa alat lepasan dirakit kembali
 * menjadi satu set — beberapa barcode lama menyatu jadi satu barcode baru,
 * disertai foto set yang sudah dirakit.
 *
 * Barcode lama TIDAK dihapus, hanya ditandai 'superseded' dan ditautkan ke
 * barcode baru, sehingga riwayat lintas siklus tetap bisa ditelusuri saat audit.
 */
class BarcodeReplacementService
{
    public function __construct(
        private readonly PublicCodeGenerator $codes,
    ) {}

    /**
     * @param  array<int, int>  $oldBatchIds
     */
    public function replace(
        array $oldBatchIds,
        User $staff,
        BatchType $newType,
        ?int $instrumentSetId,
        ?int $itemId,
        int $quantity,
        ?string $reason = null,
        ?string $photoPath = null,
    ): ItemBatch {
        if ($oldBatchIds === []) {
            throw new InvalidArgumentException('Pilih minimal satu barcode lama untuk diganti.');
        }

        return DB::transaction(function () use ($oldBatchIds, $staff, $newType, $instrumentSetId, $itemId, $quantity, $reason, $photoPath) {
            $oldBatches = ItemBatch::whereIn('id', $oldBatchIds)->lockForUpdate()->get();

            if ($oldBatches->count() !== count($oldBatchIds)) {
                throw new InvalidArgumentException('Sebagian barcode lama tidak ditemukan.');
            }

            // Semua barcode lama harus milik unit yang sama, supaya kepemilikan
            // alat tidak tertukar antar unit saat perakitan ulang.
            $unitIds = $oldBatches->pluck('origin_unit_id')->unique();
            if ($unitIds->count() > 1) {
                throw new InvalidArgumentException('Barcode lama berasal dari unit berbeda dan tidak bisa digabung.');
            }

            foreach ($oldBatches as $old) {
                if (! $old->status->isActive()) {
                    throw new InvalidArgumentException("Barcode {$old->public_code} sudah tidak aktif dan tidak bisa diganti.");
                }

                if ($old->supersededBy()->exists()) {
                    throw new InvalidArgumentException("Barcode {$old->public_code} sudah pernah diganti sebelumnya.");
                }
            }

            $reference = $oldBatches->first();
            $now = now();

            $new = ItemBatch::create([
                'public_code' => $this->codes->generate(),
                'batch_type' => $newType,
                'instrument_set_id' => $newType === BatchType::Set ? $instrumentSetId : null,
                'item_id' => $newType === BatchType::Individual ? $itemId : null,
                'quantity' => $newType === BatchType::Set ? 1 : $quantity,
                'status' => ItemBatchStatus::CleanPendingPack,
                'status_changed_at' => $now,
                'origin_unit_id' => $reference->origin_unit_id,
                'current_delivery_order_id' => $reference->current_delivery_order_id,
                'delivery_order_line_id' => $reference->delivery_order_line_id,
                'assembled_photo_path' => $photoPath,
            ]);

            ItemBatchEvent::create([
                'item_batch_id' => $new->id,
                'delivery_order_id' => $new->current_delivery_order_id,
                'from_status' => null,
                'to_status' => ItemBatchStatus::CleanPendingPack,
                'actor_user_id' => $staff->id,
                'input_method' => ScanInputMethod::Manual,
                'station_context' => 'Penggantian Barcode',
                'note' => 'Barcode baru menggantikan: '.$oldBatches->pluck('public_code')->join(', '),
                'occurred_at' => $now,
            ]);

            foreach ($oldBatches as $old) {
                $from = $old->status;

                $old->forceFill([
                    'status' => ItemBatchStatus::Superseded,
                    'status_changed_at' => $now,
                ])->save();

                ItemBatchEvent::create([
                    'item_batch_id' => $old->id,
                    'delivery_order_id' => $old->current_delivery_order_id,
                    'from_status' => $from,
                    'to_status' => ItemBatchStatus::Superseded,
                    'actor_user_id' => $staff->id,
                    'input_method' => ScanInputMethod::Manual,
                    'station_context' => 'Penggantian Barcode',
                    'note' => "Digantikan barcode baru {$new->public_code}.".($reason ? " Alasan: {$reason}" : ''),
                    'occurred_at' => $now,
                ]);

                ItemBatchSupersession::create([
                    'old_item_batch_id' => $old->id,
                    'new_item_batch_id' => $new->id,
                    'created_by_user_id' => $staff->id,
                    'reason' => $reason,
                ]);
            }

            return $new->refresh();
        });
    }
}
