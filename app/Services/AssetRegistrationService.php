<?php

namespace App\Services;

use App\Enums\AssetStatus;
use App\Enums\AssetType;
use App\Models\Asset;
use App\Models\AssetCode;
use App\Models\InstrumentSet;
use App\Models\Item;
use App\Models\SetContentCheck;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Mendaftarkan aset baru dan mengelola penggantian barcode-nya.
 *
 * Aturan penting: barcode LAMA tetap berlaku sampai barcode baru benar-benar
 * discan di menu "Scan Barcode Baru". Label fisiknya memang sudah dibuang saat
 * kemasan dibuka, tapi karena sudah discan masuk, sistem tetap mengenalinya,
 * sehingga pelacakan alat tidak pernah terputus selama pencucian.
 */
class AssetRegistrationService
{
    public function __construct(
        private readonly PublicCodeGenerator $codes,
        private readonly AssetTransitionService $transitions,
    ) {}

    /**
     * Mendaftarkan aset baru ke sistem (alat baru masuk inventaris RS).
     * Aset lahir langsung berstatus tersedia di gudang steril.
     */
    public function register(
        AssetType $type,
        ?int $instrumentSetId,
        ?int $itemId,
        User $actor,
        ?int $batchId = null,
        ?string $code = null,
    ): Asset {
        if ($type === AssetType::Set && ! $instrumentSetId) {
            throw new InvalidArgumentException('Aset bertipe set wajib memilih jenis set.');
        }

        if ($type === AssetType::Item && ! $itemId) {
            throw new InvalidArgumentException('Aset bertipe alat satuan wajib memilih jenis alat.');
        }

        return DB::transaction(function () use ($type, $instrumentSetId, $itemId, $actor, $batchId, $code) {
            $finalCode = $code ? PublicCodeGenerator::normalize($code) : $this->codes->generate();

            if (Asset::where('current_code', $finalCode)->exists() || AssetCode::where('code', $finalCode)->exists()) {
                throw new InvalidArgumentException("Barcode {$finalCode} sudah dipakai aset lain.");
            }

            $asset = Asset::create([
                'current_code' => $finalCode,
                'asset_type' => $type,
                'instrument_set_id' => $type === AssetType::Set ? $instrumentSetId : null,
                'item_id' => $type === AssetType::Item ? $itemId : null,
                'status' => AssetStatus::Available,
                'status_changed_at' => now(),
                'is_complete' => true,
                'batch_id' => $batchId,
            ]);

            AssetCode::create([
                'asset_id' => $asset->id,
                'code' => $finalCode,
                'issued_by_user_id' => $actor->id,
                'issued_at' => now(),
            ]);

            $this->transitions->recordCreation($asset, $actor, "Aset didaftarkan dengan barcode {$finalCode}.");

            return $asset;
        });
    }

    /**
     * Memasang barcode baru pada aset yang selesai dekontaminasi & pengemasan.
     *
     * Untuk aset bertipe set, hasil pemeriksaan isi ikut dicatat: isi yang
     * disilang menandai set sebagai tidak lengkap, tapi prosesnya TETAP boleh
     * lanjut, penahanan diserahkan pada keputusan manusia, bukan sistem.
     *
     * @param  array<int, array{item_id: int, expected_quantity: int, is_present: bool, note: ?string}>  $contentChecks
     */
    public function issueNewCode(
        Asset $asset,
        string $newCode,
        User $actor,
        ?string $photoPath = null,
        array $contentChecks = [],
    ): Asset {
        $normalized = PublicCodeGenerator::normalize($newCode);

        if ($normalized === '') {
            throw new InvalidArgumentException('Barcode baru wajib diisi.');
        }

        return DB::transaction(function () use ($asset, $normalized, $actor, $photoPath, $contentChecks) {
            /** @var Asset $locked */
            $locked = Asset::whereKey($asset->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->status !== AssetStatus::Washing) {
                throw new InvalidArgumentException(
                    "Barcode baru hanya dipasang setelah dekontaminasi. Alat ini berstatus \"{$locked->status->label()}\"."
                );
            }

            $clash = AssetCode::where('code', $normalized)->where('asset_id', '!=', $locked->id)->exists();

            if ($clash || Asset::where('current_code', $normalized)->where('id', '!=', $locked->id)->exists()) {
                throw new InvalidArgumentException("Barcode {$normalized} sudah dipakai aset lain.");
            }

            $oldCode = $locked->current_code;

            // Barcode lama baru berhenti berlaku tepat di titik ini.
            AssetCode::where('asset_id', $locked->id)
                ->whereNull('retired_at')
                ->update(['retired_at' => now()]);

            AssetCode::create([
                'asset_id' => $locked->id,
                'code' => $normalized,
                'issued_by_user_id' => $actor->id,
                'issued_at' => now(),
            ]);

            $locked->forceFill([
                'current_code' => $normalized,
                'assembled_photo_path' => $photoPath ?: $locked->assembled_photo_path,
            ])->save();

            $missing = $this->recordContentChecks($locked, $contentChecks, $actor);

            $this->transitions->transition(
                $locked,
                AssetStatus::Packed,
                $actor,
                \App\Enums\ScanInputMethod::Manual,
                'Scan Barcode Baru',
                "Barcode {$oldCode} diganti menjadi {$normalized}."
                    .($missing > 0 ? " Set tidak lengkap: {$missing} isi tidak ditemukan." : ''),
                ['old_code' => $oldCode, 'new_code' => $normalized, 'missing_count' => $missing],
            );

            return $locked->refresh();
        });
    }

    /**
     * Menyimpan hasil checklist isi set dan menandai kelengkapannya.
     *
     * @param  array<int, array{item_id: int, expected_quantity: int, is_present: bool, note: ?string}>  $checks
     * @return int jumlah isi yang tidak ditemukan
     */
    private function recordContentChecks(Asset $asset, array $checks, User $actor): int
    {
        if ($asset->asset_type !== AssetType::Set || $checks === []) {
            return 0;
        }

        $now = now();
        $missing = 0;

        foreach ($checks as $check) {
            $present = (bool) ($check['is_present'] ?? true);
            $missing += $present ? 0 : 1;

            SetContentCheck::create([
                'asset_id' => $asset->id,
                'item_id' => $check['item_id'],
                'expected_quantity' => $check['expected_quantity'] ?? 1,
                'is_present' => $present,
                'checked_by_user_id' => $actor->id,
                'checked_at' => $now,
                'note' => $check['note'] ?? null,
            ]);
        }

        $asset->forceFill(['is_complete' => $missing === 0])->save();

        return $missing;
    }

    /**
     * Daftar isi yang diharapkan ada di dalam sebuah set, untuk ditampilkan
     * sebagai checklist saat petugas memasang barcode baru.
     *
     * @return \Illuminate\Support\Collection<int, array{item_id: int, name: string, code: string, quantity: int}>
     */
    public function expectedContents(Asset $asset): \Illuminate\Support\Collection
    {
        if ($asset->asset_type !== AssetType::Set || ! $asset->instrument_set_id) {
            return collect();
        }

        return InstrumentSet::with('items')
            ->find($asset->instrument_set_id)
            ?->items
            ->map(fn (Item $item) => [
                'item_id' => $item->id,
                'name' => $item->name,
                'code' => $item->code,
                'quantity' => (int) $item->pivot->quantity,
            ]) ?? collect();
    }
}
