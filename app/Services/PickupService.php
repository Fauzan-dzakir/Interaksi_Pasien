<?php

namespace App\Services;

use App\Enums\DeliveryMethod;
use App\Enums\ItemBatchStatus;
use App\Enums\ScanInputMethod;
use App\Models\ItemBatch;
use App\Models\Pickup;
use App\Models\User;
use App\Notifications\ItemsReadyForPickup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use InvalidArgumentException;

/**
 * Serah-terima alat steril dari CSSD kembali ke unit — menutup satu siklus.
 * Mencakup dua jalur pada alur: diambil unit, atau "Dikirim Langsung" oleh CSSD.
 */
class PickupService
{
    public function __construct(
        private readonly DocumentNumberGenerator $numbers,
        private readonly ItemBatchTransitionService $transitions,
        private readonly DeliveryOrderService $orders,
    ) {}

    /**
     * CSSD menyiapkan alat untuk satu unit: status berpindah ke "Siap Diambil"
     * dan unit langsung diberi notifikasi.
     *
     * @param  array<int, int>  $batchIds
     */
    public function dispatch(
        User $staff,
        int $unitId,
        array $batchIds,
        DeliveryMethod $method,
        ?string $receiverName = null,
        ?string $notes = null,
    ): Pickup {
        if ($batchIds === []) {
            throw new InvalidArgumentException('Pilih minimal satu alat untuk diserahkan.');
        }

        return DB::transaction(function () use ($staff, $unitId, $batchIds, $method, $receiverName, $notes) {
            $batches = ItemBatch::whereIn('id', $batchIds)
                ->where('origin_unit_id', $unitId)
                ->get();

            if ($batches->count() !== count($batchIds)) {
                throw new InvalidArgumentException('Ada alat yang tidak ditemukan atau bukan milik unit ini.');
            }

            $pickup = Pickup::create([
                'pickup_number' => $this->numbers->pickup(),
                'origin_unit_id' => $unitId,
                'delivery_method' => $method,
                'dispatched_by_user_id' => $staff->id,
                'dispatched_at' => now(),
                'receiver_name' => $receiverName,
                'notes' => $notes,
            ]);

            foreach ($batches as $batch) {
                $this->transitions->transition(
                    $batch,
                    ItemBatchStatus::ReadyForPickup,
                    $staff,
                    ScanInputMethod::Manual,
                    'Distribusi',
                    "Disiapkan pada {$pickup->pickup_number} ({$method->label()}).",
                );

                $pickup->itemBatches()->attach($batch->id);
            }

            $this->notifyUnit($pickup, $batches->count());

            return $pickup->refresh();
        });
    }

    /**
     * Unit menekan "Diterima" — inilah titik yang membuktikan alat berpindah
     * tanggung jawab dari CSSD ke unit, lengkap dengan siapa & jam berapa.
     */
    public function confirmReceipt(Pickup $pickup, User $receiver): Pickup
    {
        return DB::transaction(function () use ($pickup, $receiver) {
            if ($pickup->isConfirmed()) {
                return $pickup;
            }

            $affectedOrders = collect();

            foreach ($pickup->itemBatches as $batch) {
                $this->transitions->transition(
                    $batch,
                    ItemBatchStatus::PickedUp,
                    $receiver,
                    ScanInputMethod::Manual,
                    'Konfirmasi Penerimaan Unit',
                    "Diterima unit pada {$pickup->pickup_number}.",
                );

                if ($batch->currentDeliveryOrder) {
                    $affectedOrders->put($batch->currentDeliveryOrder->id, $batch->currentDeliveryOrder);
                }
            }

            $pickup->update([
                'confirmed_by_user_id' => $receiver->id,
                'confirmed_at' => now(),
            ]);

            // Order dianggap selesai kalau seluruh alat di dalamnya sudah kembali ke unit.
            foreach ($affectedOrders as $order) {
                $this->orders->syncStatus($order->fresh(), $receiver);
            }

            return $pickup->refresh();
        });
    }

    private function notifyUnit(Pickup $pickup, int $batchCount): void
    {
        $recipients = User::active()->where('unit_id', $pickup->origin_unit_id)->get();

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new ItemsReadyForPickup($pickup, $batchCount));
        }
    }
}
