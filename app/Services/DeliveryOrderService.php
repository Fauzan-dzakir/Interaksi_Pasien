<?php

namespace App\Services;

use App\Enums\BatchType;
use App\Enums\DeliveryOrderStatus;
use App\Enums\ItemBatchStatus;
use App\Enums\ZoneBucket;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderEvent;
use App\Models\DeliveryOrderLine;
use App\Models\DeliveryOrderLineItemCheck;
use App\Models\ItemBatch;
use App\Models\User;
use App\Notifications\IntakeRecorded;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class DeliveryOrderService
{
    public function __construct(
        private readonly DocumentNumberGenerator $numbers,
        private readonly PublicCodeGenerator $codes,
        private readonly ItemBatchTransitionService $transitions,
    ) {}

    /**
     * Unit membuat tiket pengiriman. Hanya informasi dasar — rincian alat
     * sengaja tidak diminta di sini karena pendataan fisik tugas CSSD.
     */
    public function create(User $submitter, array $attributes): DeliveryOrder
    {
        return DB::transaction(function () use ($submitter, $attributes) {
            $order = DeliveryOrder::create([
                'order_number' => $this->numbers->deliveryOrder(),
                'origin_unit_id' => $submitter->unit_id,
                'submitted_by_user_id' => $submitter->id,
                'courier_name' => $attributes['courier_name'],
                'sent_at' => $attributes['sent_at'],
                'box_count' => $attributes['box_count'],
                'is_cito' => $attributes['is_cito'] ?? false,
                'needed_at' => $attributes['needed_at'] ?? null,
                'pickup_location' => $attributes['pickup_location'] ?? null,
                'notes' => $attributes['notes'] ?? null,
                'photos' => $attributes['photos'] ?? null,
                'status' => DeliveryOrderStatus::PendingCssdIntake,
            ]);

            $this->recordEvent($order, null, DeliveryOrderStatus::PendingCssdIntake, $submitter, 'Order dibuat oleh unit.');

            return $order;
        });
    }

    /**
     * CSSD menyimpan hasil pendataan fisik ("Simpan Pendataan").
     *
     * Dari tiap baris pendataan dibuatkan batch beserta label QR-nya: qty N pada
     * baris manapun (Per Set maupun Per Barang) menghasilkan N batch terpisah,
     * masing-masing dengan QR sendiri — supaya tiap unit fisik alat bisa
     * dilacak/discan sendiri-sendiri (mis. dari 2 selang yang sama, cuma 1 yang
     * dipakai unit, sisanya tetap tercatat belum dipakai).
     *
     * @param  array<int, array{line_type: string, instrument_set_id: ?int, item_id: ?int, quantity: int, notes: ?string}>  $lines
     */
    public function recordIntake(DeliveryOrder $order, User $staff, array $lines): DeliveryOrder
    {
        return DB::transaction(function () use ($order, $staff, $lines) {
            $now = now();

            foreach ($lines as $row) {
                $type = BatchType::from($row['line_type']);

                $line = DeliveryOrderLine::create([
                    'delivery_order_id' => $order->id,
                    'line_type' => $type,
                    'instrument_set_id' => $type === BatchType::Set ? $row['instrument_set_id'] : null,
                    'item_id' => $type === BatchType::Individual ? $row['item_id'] : null,
                    'quantity' => $row['quantity'],
                    'notes' => $row['notes'] ?? null,
                    'recorded_by_user_id' => $staff->id,
                    'recorded_at' => $now,
                ]);

                if ($type === BatchType::Set && ! empty($row['set_checks'])) {
                    foreach ($row['set_checks'] as $check) {
                        DeliveryOrderLineItemCheck::create([
                            'delivery_order_line_id' => $line->id,
                            'item_id' => $check['item_id'],
                            'is_present' => (bool) ($check['is_present'] ?? true),
                            'note' => ($check['note'] ?? '') !== '' ? $check['note'] : null,
                        ]);
                    }
                }

                $batchCount = (int) $row['quantity'];
                $perBatchQty = 1;

                for ($i = 0; $i < $batchCount; $i++) {
                    $batch = ItemBatch::create([
                        'public_code' => $this->codes->generate(),
                        'batch_type' => $type,
                        'instrument_set_id' => $line->instrument_set_id,
                        'item_id' => $line->item_id,
                        'quantity' => $perBatchQty,
                        'status' => ItemBatchStatus::ReturnedDirty,
                        'status_changed_at' => $now,
                        'origin_unit_id' => $order->origin_unit_id,
                        'current_delivery_order_id' => $order->id,
                        'delivery_order_line_id' => $line->id,
                    ]);

                    $this->transitions->recordCreation($batch, $staff, 'Didata saat penerimaan alat kotor.');
                }
            }

            $from = $order->status;

            $order->update([
                'status' => DeliveryOrderStatus::IntakeRecorded,
                'intake_recorded_at' => $now,
                'intake_recorded_by_user_id' => $staff->id,
            ]);

            $this->recordEvent($order, $from, DeliveryOrderStatus::IntakeRecorded, $staff, 'Pendataan alat disimpan.');

            $this->notifyUnit($order);

            return $order->refresh();
        });
    }

    public function cancel(DeliveryOrder $order, User $actor, string $reason): DeliveryOrder
    {
        return DB::transaction(function () use ($order, $actor, $reason) {
            $from = $order->status;
            $order->update(['status' => DeliveryOrderStatus::Cancelled]);
            $this->recordEvent($order, $from, DeliveryOrderStatus::Cancelled, $actor, $reason);

            return $order->refresh();
        });
    }

    /**
     * Menyelaraskan status order dengan kondisi batch di dalamnya.
     * Dipanggil setiap kali ada batch yang berpindah tahap.
     */
    public function syncStatus(DeliveryOrder $order, ?User $actor = null): void
    {
        if (! $order->status->isOpen()) {
            return;
        }

        $batches = $order->itemBatches()->get();

        if ($batches->isEmpty()) {
            return;
        }

        // Ada alat yang dinyatakan hilang/rusak (Koreksi Admin) — order TIDAK boleh
        // terlihat "Sedang Diproses"/"Selesai" seolah semua baik-baik saja. Ini prioritas
        // TERTINGGI: tetap ditandai walau alat lain di order yang sama sudah normal selesai,
        // supaya staf tahu order ini masih butuh tindak lanjut.
        $hasIssue = $batches->contains(
            fn (ItemBatch $b) => in_array($b->status, [ItemBatchStatus::Lost, ItemBatchStatus::Retired], true)
        );

        $allReturned = $batches->every(
            fn (ItemBatch $b) => in_array($b->status, [
                ItemBatchStatus::PickedUp,
                ItemBatchStatus::InUse,
                ItemBatchStatus::Superseded,
                ItemBatchStatus::Lost,
                ItemBatchStatus::Retired,
            ], true)
        );

        // Sudah keluar dari zona kotor/bersih (tersimpan, siap diambil, atau malah
        // sudah diambil) — dari sisi CSSD pekerjaan cuci/sterilisasinya tuntas,
        // tinggal menunggu unit mengambil. Tanpa tingkatan ini, order akan terus
        // berlabel "Sedang Diproses" walau CSSD sudah tidak mengerjakan apa-apa lagi.
        $doneProcessing = $batches->every(
            fn (ItemBatch $b) => ! in_array($b->zone(), [ZoneBucket::Dirty, ZoneBucket::Clean], true)
        );

        $target = match (true) {
            $hasIssue => DeliveryOrderStatus::HasIssue,
            $allReturned => DeliveryOrderStatus::Completed,
            $doneProcessing => DeliveryOrderStatus::ReadyForDistribution,
            default => DeliveryOrderStatus::Processing,
        };

        if ($order->status === $target) {
            return;
        }

        $from = $order->status;
        $order->update(['status' => $target]);

        $this->recordEvent($order, $from, $target, $actor, match ($target) {
            DeliveryOrderStatus::HasIssue => 'Ada alat pada order ini yang dinyatakan hilang/rusak — perlu tindak lanjut.',
            DeliveryOrderStatus::Completed => 'Seluruh alat sudah kembali ke unit.',
            DeliveryOrderStatus::ReadyForDistribution => 'CSSD selesai memproses seluruh alat, siap didistribusikan.',
            default => 'Alat mulai diproses di CSSD.',
        });
    }

    private function notifyUnit(DeliveryOrder $order): void
    {
        $recipients = User::active()
            ->where('unit_id', $order->origin_unit_id)
            ->get();

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new IntakeRecorded($order));
        }
    }

    private function recordEvent(
        DeliveryOrder $order,
        ?DeliveryOrderStatus $from,
        DeliveryOrderStatus $to,
        ?User $actor,
        ?string $note,
    ): void {
        DeliveryOrderEvent::create([
            'delivery_order_id' => $order->id,
            'from_status' => $from,
            'to_status' => $to,
            'actor_user_id' => $actor?->id,
            'note' => $note,
            'occurred_at' => now(),
        ]);
    }
}
