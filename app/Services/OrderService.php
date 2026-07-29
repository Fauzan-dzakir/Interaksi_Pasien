<?php

namespace App\Services;

use App\Enums\AssetStatus;
use App\Enums\FulfillmentMethod;
use App\Enums\OrderStatus;
use App\Enums\ScanInputMethod;
use App\Models\Asset;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\OrderPhoto;
use App\Models\User;
use App\Notifications\OrderReady;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use InvalidArgumentException;

/**
 * Alur pesanan cuci alat, seperti jasa laundry.
 *
 * Unit TIDAK melakukan pendataan barang. Mereka cukup melampirkan foto alat
 * kotor yang dikirim beserta catatan. Pendataan sesungguhnya terjadi saat CSSD
 * men-scan barcode tiap alat yang datang, karena barcode lama masih berlaku.
 */
class OrderService
{
    public function __construct(
        private readonly DocumentNumberGenerator $numbers,
        private readonly AssetTransitionService $transitions,
    ) {}

    /**
     * Unit membuat pesanan cuci: foto barang (wajib), catatan, dan penanda CITO.
     * Waktu "dibutuhkan pada" hanya relevan untuk pesanan CITO.
     *
     * @param  array<int, string>  $photoPaths
     */
    public function create(
        User $requester,
        array $photoPaths,
        ?string $notes = null,
        bool $isCito = false,
        ?string $neededAt = null,
        ?int $batchId = null,
    ): Order {
        if ($photoPaths === []) {
            throw new InvalidArgumentException('Lampirkan minimal satu foto barang yang akan dicuci.');
        }

        return DB::transaction(function () use ($requester, $photoPaths, $notes, $isCito, $neededAt, $batchId) {
            $order = Order::create([
                'order_number' => $this->numbers->order(),
                'unit_id' => $requester->unit_id,
                'batch_id' => $batchId,
                'requested_by_user_id' => $requester->id,
                'status' => OrderStatus::Pending,
                // Kolom lama tetap terisi nilai bawaan; cara serah tidak lagi dipilih unit.
                'fulfillment_method' => FulfillmentMethod::Pickup,
                'is_cito' => $isCito,
                'needed_at' => $isCito ? $neededAt : null,
                'notes' => $notes,
            ]);

            foreach ($photoPaths as $path) {
                OrderPhoto::create([
                    'order_id' => $order->id,
                    'photo_path' => $path,
                    'uploaded_by_user_id' => $requester->id,
                ]);
            }

            $this->recordEvent($order, null, OrderStatus::Pending, $requester, $isCito
                ? 'Pesanan cuci CITO dibuat unit, alat dibutuhkan segera.'
                : 'Pesanan cuci dibuat unit.');

            return $order->refresh();
        });
    }

    /**
     * CSSD men-scan satu alat kotor yang datang bersama pesanan ini.
     *
     * Di sinilah pendataan sesungguhnya terjadi: alat teridentifikasi lewat
     * barcode lamanya, menempel ke pesanan, dan masuk antrian pencucian.
     */
    public function receiveDirtyAsset(
        Order $order,
        Asset $asset,
        User $staff,
        ScanInputMethod $inputMethod = ScanInputMethod::Manual,
    ): void {
        DB::transaction(function () use ($order, $asset, $staff, $inputMethod) {
            if (! $order->status->isOpen() || in_array($order->status, [OrderStatus::ReadyForPickup, OrderStatus::Delivering], true)) {
                throw new InvalidArgumentException('Pesanan ini sudah tidak menerima alat baru.');
            }

            if (! $asset->status->isAtUnit()) {
                throw new InvalidArgumentException(
                    "{$asset->current_code} berstatus \"{$asset->status->label()}\", bukan alat yang sedang berada di unit."
                );
            }

            $this->transitions->transition(
                $asset,
                AssetStatus::ReturnPending,
                $staff,
                $inputMethod,
                'Terima Pesanan Cuci',
                "Diterima CSSD pada pesanan {$order->order_number}.",
            );

            $order->assets()->syncWithoutDetaching([$asset->id]);

            if ($order->status === OrderStatus::Pending) {
                $order->update(['status' => OrderStatus::Preparing, 'prepared_by_user_id' => $staff->id]);
                $this->recordEvent($order, OrderStatus::Pending, OrderStatus::Preparing, $staff, 'CSSD menerima alat kotor dari unit.');
            }
        });
    }

    /**
     * Seluruh alat pada pesanan sudah selesai steril; CSSD menyerahkannya
     * kembali ke unit disertai foto bukti serah terima.
     */
    public function handBack(Order $order, User $staff, ?string $photoPath = null, ?string $receiverName = null): Order
    {
        return DB::transaction(function () use ($order, $staff, $photoPath, $receiverName) {
            $assets = $order->assets()->get();

            if ($assets->isEmpty()) {
                throw new InvalidArgumentException('Belum ada alat yang diterima pada pesanan ini.');
            }

            foreach ($assets as $asset) {
                if ($asset->status !== AssetStatus::Available) {
                    throw new InvalidArgumentException(
                        "{$asset->current_code} belum selesai diproses (status: {$asset->status->label()})."
                    );
                }
            }

            foreach ($assets as $asset) {
                // Dua langkah mengikuti peta transisi: disiapkan dulu, lalu siap diambil.
                $this->transitions->transition(
                    $asset, AssetStatus::Reserved, $staff, ScanInputMethod::Manual,
                    'Pengembalian ke Unit', "Disiapkan untuk dikembalikan pada pesanan {$order->order_number}.");

                $this->transitions->transition(
                    $asset, AssetStatus::ReadyForHandover, $staff, ScanInputMethod::Manual,
                    'Pengembalian ke Unit', "Diserahkan kembali pada pesanan {$order->order_number}.");
            }

            $from = $order->status;

            $order->update([
                'status' => OrderStatus::ReadyForPickup,
                'prepared_at' => $order->prepared_at ?? now(),
                'prepared_by_user_id' => $order->prepared_by_user_id ?? $staff->id,
                'handover_photo_path' => $photoPath,
                'receiver_name' => $receiverName,
                'handed_over_at' => now(),
            ]);

            $this->recordEvent($order, $from, OrderStatus::ReadyForPickup, $staff,
                'Seluruh alat selesai steril dan diserahkan kembali ke unit.');

            $this->notifyUnit($order);

            return $order->refresh();
        });
    }

    /** Unit menyatakan alat sudah kembali, titik perpindahan tanggung jawab. */
    public function confirmReceipt(Order $order, User $receiver): Order
    {
        return DB::transaction(function () use ($order, $receiver) {
            if ($order->status === OrderStatus::Received) {
                return $order;
            }

            foreach ($order->assets as $asset) {
                $this->transitions->transition(
                    $asset,
                    AssetStatus::AtUnit,
                    $receiver,
                    ScanInputMethod::Manual,
                    'Konfirmasi Penerimaan Unit',
                    "Kembali ke unit pada pesanan {$order->order_number}.",
                );
            }

            $from = $order->status;

            $order->update([
                'status' => OrderStatus::Received,
                'received_by_user_id' => $receiver->id,
                'received_at' => now(),
            ]);

            $this->recordEvent($order, $from, OrderStatus::Received, $receiver, 'Unit mengonfirmasi alat sudah kembali.');

            return $order->refresh();
        });
    }

    /** Pesanan hanya bisa dibatalkan selama belum ada alat yang diterima CSSD. */
    public function cancel(Order $order, User $actor, string $reason): Order
    {
        return DB::transaction(function () use ($order, $actor, $reason) {
            if ($order->assets()->exists()) {
                throw new InvalidArgumentException('Alat sudah diterima CSSD, pesanan tidak bisa dibatalkan.');
            }

            $from = $order->status;
            $order->update(['status' => OrderStatus::Cancelled]);
            $this->recordEvent($order, $from, OrderStatus::Cancelled, $actor, $reason);

            return $order->refresh();
        });
    }

    private function notifyUnit(Order $order): void
    {
        $recipients = User::active()->where('unit_id', $order->unit_id)->get();

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new OrderReady($order));
        }
    }

    private function recordEvent(
        Order $order,
        ?OrderStatus $from,
        OrderStatus $to,
        ?User $actor,
        ?string $note,
    ): void {
        OrderEvent::create([
            'order_id' => $order->id,
            'from_status' => $from,
            'to_status' => $to,
            'actor_user_id' => $actor?->id,
            'note' => $note,
            'occurred_at' => now(),
        ]);
    }
}
