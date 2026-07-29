<?php

namespace App\Services;

use App\Enums\AssetStatus;
use App\Enums\ScanInputMethod;
use App\Exceptions\InvalidTransitionException;
use App\Models\Asset;
use App\Models\AssetEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Satu-satunya pintu untuk mengubah status aset.
 *
 * Tiga jaminan yang diberikan kelas ini:
 *  1. Setiap perubahan status SELALU meninggalkan jejak audit (pelaku + waktu + metode input).
 *  2. Transisi yang tidak sah ditolak tegas, bukan didiamkan.
 *  3. Dua petugas yang men-scan alat yang sama bersamaan tidak bisa saling menimpa,
 *     karena baris dikunci (lockForUpdate) di dalam satu transaksi.
 */
class AssetTransitionService
{
    /**
     * Memindahkan aset ke tahap berikutnya.
     *
     * Bersifat idempotent: scan ganda ke status yang sama (mis. scanner HID
     * terpicu dua kali) diabaikan tanpa error dan tanpa jejak ganda.
     *
     * @return bool true bila status benar-benar berubah, false bila scan berulang.
     *
     * @throws InvalidTransitionException
     */
    public function transition(
        Asset $asset,
        AssetStatus $target,
        ?User $actor,
        ScanInputMethod $inputMethod = ScanInputMethod::Manual,
        ?string $station = null,
        ?string $note = null,
        array $metadata = [],
    ): bool {
        return DB::transaction(function () use ($asset, $target, $actor, $inputMethod, $station, $note, $metadata) {
            /** @var Asset $locked */
            $locked = Asset::whereKey($asset->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->status === $target) {
                return false;
            }

            if (! $locked->status->canTransitionTo($target)) {
                throw new InvalidTransitionException($locked->status, $target, $locked->current_code);
            }

            $this->apply($locked, $target, $actor, $inputMethod, $station, $note, $metadata, false);

            $asset->refresh();

            return true;
        });
    }

    /**
     * Koreksi manual oleh Admin, boleh melompati peta transisi (mis. menandai
     * alat hilang, atau membetulkan salah scan petugas), tetapi alasannya WAJIB
     * diisi dan tetap tercatat sebagai jejak audit yang ditandai override.
     */
    public function adminOverride(Asset $asset, AssetStatus $target, User $admin, string $reason): bool
    {
        if (trim($reason) === '') {
            throw new InvalidArgumentException('Alasan koreksi wajib diisi.');
        }

        return DB::transaction(function () use ($asset, $target, $admin, $reason) {
            /** @var Asset $locked */
            $locked = Asset::whereKey($asset->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->status === $target) {
                return false;
            }

            $this->apply($locked, $target, $admin, ScanInputMethod::Manual, 'Koreksi Admin', $reason, [], true);

            $asset->refresh();

            return true;
        });
    }

    /** Mencatat kelahiran aset supaya jejak audit lengkap sejak detik pertama. */
    public function recordCreation(Asset $asset, ?User $actor, ?string $note = null): void
    {
        AssetEvent::create([
            'asset_id' => $asset->id,
            'from_status' => null,
            'to_status' => $asset->status,
            'actor_user_id' => $actor?->id,
            'input_method' => ScanInputMethod::Manual,
            'station_context' => 'Pendaftaran Aset',
            'note' => $note,
            'is_admin_override' => false,
            'occurred_at' => now(),
        ]);
    }

    /**
     * Mencatat kejadian yang tidak mengubah status, mis. pemeriksaan isi set
     * atau penggantian barcode, tetap harus terekam di jejak audit.
     */
    public function recordNote(
        Asset $asset,
        ?User $actor,
        string $station,
        string $note,
        array $metadata = [],
    ): void {
        AssetEvent::create([
            'asset_id' => $asset->id,
            'from_status' => $asset->status,
            'to_status' => $asset->status,
            'actor_user_id' => $actor?->id,
            'input_method' => ScanInputMethod::Manual,
            'station_context' => $station,
            'note' => $note,
            'is_admin_override' => false,
            'metadata' => $metadata ?: null,
            'occurred_at' => now(),
        ]);
    }

    private function apply(
        Asset $asset,
        AssetStatus $target,
        ?User $actor,
        ScanInputMethod $inputMethod,
        ?string $station,
        ?string $note,
        array $metadata,
        bool $isOverride,
    ): void {
        $from = $asset->status;

        $attributes = [
            'status' => $target,
            'status_changed_at' => now(),
        ];

        // Satu siklus dihitung selesai saat alat kembali tersedia di gudang steril.
        if ($target === AssetStatus::Available && $from === AssetStatus::Sterilizing) {
            $attributes['cycle_count'] = $asset->cycle_count + 1;
        }

        $asset->forceFill($attributes)->save();

        AssetEvent::create([
            'asset_id' => $asset->id,
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
