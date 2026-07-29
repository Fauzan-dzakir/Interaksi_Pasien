<?php

namespace App\Services;

use App\Enums\AssetStatus;
use App\Enums\ScanInputMethod;
use App\Models\Asset;
use App\Models\ReturnShipment;
use App\Models\User;
use App\Notifications\ReturnAwaitingConfirmation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use InvalidArgumentException;

/**
 * Pengembalian alat kotor dari unit ke CSSD, memakai KONFIRMASI DUA SISI.
 *
 * Unit menyatakan sudah mengirim, lalu CSSD menyatakan sudah menerima. Selama
 * CSSD belum konfirmasi, alat menggantung di status "Menunggu Konfirmasi CSSD",
 * di situlah selisih antar-serah terdeteksi, bukan baru ketahuan saat audit.
 *
 * Di form pengiriman inilah unit menentukan batch-nya dipesan ulang atau dilepas.
 */
class ReturnService
{
    public function __construct(
        private readonly DocumentNumberGenerator $numbers,
        private readonly AssetTransitionService $transitions,
    ) {}

    /**
     * Sisi unit: menyatakan alat kotor sudah dikirim ke CSSD.
     *
     * @param  array<int, int>  $assetIds
     * @param  array<int, int>  $usedAssetIds  aset yang benar-benar terpakai
     */
    public function send(
        User $sender,
        array $assetIds,
        array $usedAssetIds = [],
        ?int $batchId = null,
        bool $reorderBatch = true,
        ?string $photoPath = null,
        ?string $courierName = null,
        ?string $notes = null,
    ): ReturnShipment {
        if ($assetIds === []) {
            throw new InvalidArgumentException('Pilih minimal satu alat untuk dikembalikan.');
        }

        return DB::transaction(function () use ($sender, $assetIds, $usedAssetIds, $batchId, $reorderBatch, $photoPath, $courierName, $notes) {
            $assets = Asset::whereIn('id', $assetIds)->lockForUpdate()->get();

            if ($assets->count() !== count($assetIds)) {
                throw new InvalidArgumentException('Sebagian alat tidak ditemukan.');
            }

            foreach ($assets as $asset) {
                if (! $asset->status->isAtUnit()) {
                    throw new InvalidArgumentException(
                        "{$asset->current_code} berstatus \"{$asset->status->label()}\", bukan alat yang sedang ada di unit."
                    );
                }
            }

            $shipment = ReturnShipment::create([
                'return_number' => $this->numbers->returnShipment(),
                'unit_id' => $sender->unit_id,
                'batch_id' => $batchId,
                'sent_by_user_id' => $sender->id,
                'sent_at' => now(),
                'sender_photo_path' => $photoPath,
                'courier_name' => $courierName ?? $sender->name,
                'reorder_batch' => $reorderBatch,
                'notes' => $notes,
            ]);

            foreach ($assets as $asset) {
                $wasUsed = in_array($asset->id, $usedAssetIds, true) || $asset->status === AssetStatus::InUse;

                $this->transitions->transition(
                    $asset,
                    AssetStatus::ReturnPending,
                    $sender,
                    ScanInputMethod::Manual,
                    'Pengiriman Balik oleh Unit',
                    "Dikirim balik pada {$shipment->return_number}."
                        .($wasUsed ? ' Alat terpakai.' : ' Alat tidak terpakai.'),
                );

                $shipment->assets()->attach($asset->id, ['was_used' => $wasUsed]);
            }

            $this->notifyCssd($shipment, $assets->count());

            return $shipment->refresh();
        });
    }

    /**
     * Sisi CSSD: menyatakan alat kotor sudah benar-benar diterima.
     * Barcode lama TETAP berlaku setelah titik ini, baru berganti saat
     * barcode baru discan seusai dekontaminasi.
     */
    public function confirmReceipt(ReturnShipment $shipment, User $staff, ?string $photoPath = null): ReturnShipment
    {
        return DB::transaction(function () use ($shipment, $staff, $photoPath) {
            if ($shipment->isConfirmed()) {
                return $shipment;
            }

            foreach ($shipment->assets as $asset) {
                $this->transitions->transition(
                    $asset,
                    AssetStatus::Washing,
                    $staff,
                    ScanInputMethod::Manual,
                    'Konfirmasi Terima CSSD',
                    "Diterima CSSD pada {$shipment->return_number}, masuk pencucian.",
                );
            }

            $shipment->update([
                'confirmed_by_user_id' => $staff->id,
                'confirmed_at' => now(),
                'receiver_photo_path' => $photoPath,
            ]);

            return $shipment->refresh();
        });
    }

    /**
     * Apakah aset ini harus dilepas dari batch saat siklusnya selesai?
     * Ditentukan dari keputusan "order ulang" pada pengembalian terakhirnya.
     */
    public function shouldReleaseFromBatch(Asset $asset): bool
    {
        if ($asset->batch_id === null) {
            return false;
        }

        $latest = ReturnShipment::query()
            ->whereHas('assets', fn ($q) => $q->where('assets.id', $asset->id))
            ->latest('sent_at')
            ->first();

        return $latest !== null && ! $latest->reorder_batch;
    }

    private function notifyCssd(ReturnShipment $shipment, int $assetCount): void
    {
        $recipients = User::active()
            ->where('role', \App\Enums\UserRole::CssdStaff)
            ->get();

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new ReturnAwaitingConfirmation($shipment, $assetCount));
        }
    }
}
