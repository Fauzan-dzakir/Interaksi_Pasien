<?php

namespace App\Services;

use App\Enums\AssetStatus;
use App\Enums\ScanInputMethod;
use App\Enums\SterilizationMethod;
use App\Models\Asset;
use App\Models\Batch;
use App\Models\SterilizationRecord;
use App\Models\User;
use App\Notifications\AssetsReadyInStock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use InvalidArgumentException;

/**
 * Tahap akhir siklus: sterilisasi dan pengembalian alat ke gudang steril.
 *
 * Di titik penyelesaian inilah keputusan "order ulang" dari unit dieksekusi:
 * alat yang batch-nya dipesan ulang tetap menempel ke batch unit, sedangkan
 * yang tidak dipesan ulang dilepas menjadi available stock.
 */
class SterilizationService
{
    public function __construct(
        private readonly AssetTransitionService $transitions,
        private readonly ReturnService $returns,
        private readonly DocumentNumberGenerator $numbers,
    ) {}

    /** Memasukkan aset yang sudah dikemas & berbarcode baru ke proses sterilisasi. */
    public function startSterilizing(
        Asset $asset,
        User $staff,
        SterilizationMethod $method,
        ScanInputMethod $inputMethod = ScanInputMethod::Manual,
    ): bool {
        $asset->forceFill(['sterilization_method' => $method])->save();

        return $this->transitions->transition(
            $asset,
            AssetStatus::Sterilizing,
            $staff,
            $inputMethod,
            'Sterilisasi',
            "Metode: {$method->label()}.",
        );
    }

    /**
     * Menyelesaikan satu aset: kembali ke gudang steril, dan batch dilepas
     * bila unit memang tidak memesan ulang.
     */
    public function complete(
        Asset $asset,
        User $staff,
        ScanInputMethod $inputMethod = ScanInputMethod::Manual,
    ): bool {
        return DB::transaction(function () use ($asset, $staff, $inputMethod) {
            $release = $this->returns->shouldReleaseFromBatch($asset);
            $batchName = $asset->batch?->name;

            $changed = $this->transitions->transition(
                $asset,
                AssetStatus::Available,
                $staff,
                $inputMethod,
                'Penyelesaian Sterilisasi',
                $release
                    ? "Selesai steril. Dilepas dari batch \"{$batchName}\" ke stok tersedia."
                    : 'Selesai steril, kembali tersedia.',
            );

            if ($changed && $release) {
                $asset->forceFill(['batch_id' => null])->save();
            }

            return $changed;
        });
    }

    /**
     * Menyelesaikan seluruh aset dalam satu batch sekaligus, petugas tidak perlu
     * men-scan satu per satu saat satu muatan autoclave keluar bersamaan.
     *
     * @return array{completed: int, skipped: int}
     */
    public function completeBatch(Batch $batch, User $staff): array
    {
        $assets = $batch->assets()->where('status', AssetStatus::Sterilizing)->get();

        $completed = 0;

        foreach ($assets as $asset) {
            if ($this->complete($asset, $staff)) {
                $completed++;
            }
        }

        if ($completed > 0) {
            $this->notifyUnit($batch, $completed);
        }

        return [
            'completed' => $completed,
            'skipped' => $batch->assets()->count() - $completed,
        ];
    }

    /**
     * Mencatat satu proses sterilisasi sebagai dasar laporan PDF.
     * Tahapan, jam, dan petugas TIDAK diinput di sini, diambil dari jejak audit.
     *
     * @param  array<int, int>  $assetIds
     */
    public function createRecord(
        User $staff,
        SterilizationMethod $method,
        array $assetIds,
        ?int $batchId = null,
        ?int $unitId = null,
        ?string $notes = null,
    ): SterilizationRecord {
        if ($assetIds === []) {
            throw new InvalidArgumentException('Pilih minimal satu alat untuk dimasukkan ke laporan.');
        }

        return DB::transaction(function () use ($staff, $method, $assetIds, $batchId, $unitId, $notes) {
            $record = SterilizationRecord::create([
                'record_number' => $this->numbers->sterilizationRecord(),
                'batch_id' => $batchId,
                'unit_id' => $unitId,
                'method' => $method,
                'biological_indicator_result' => \App\Enums\BiologicalIndicatorResult::Pending,
                'created_by_user_id' => $staff->id,
                'notes' => $notes,
            ]);

            $record->assets()->attach($assetIds);

            return $record->refresh();
        });
    }

    private function notifyUnit(Batch $batch, int $count): void
    {
        $recipients = User::active()->where('unit_id', $batch->unit_id)->get();

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new AssetsReadyInStock($batch, $count));
        }
    }
}
