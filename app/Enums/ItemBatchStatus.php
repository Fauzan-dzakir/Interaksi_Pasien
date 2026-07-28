<?php

namespace App\Enums;

/**
 * Siklus hidup satu batch alat (satu barcode/QR).
 *
 * Alur normal:
 *   returned_dirty -> dirty_zone_washing -> dirty_zone_drying -> cleanliness_check_pending
 *   -> clean_pending_pack -> packaging -> sterilizing -> sterile_check_pending
 *   -> in_storage -> ready_for_pickup -> picked_up -> in_use -> (kembali ke returned_dirty)
 *
 * Ada dua titik loop-back sesuai alur CSSD di lapangan:
 *   - cek kebersihan gagal  -> cuci ulang
 *   - cek label steril gagal -> kemas ulang atau cuci ulang (dipilih petugas)
 */
enum ItemBatchStatus: string
{
    // --- Zona Kotor ---
    case ReturnedDirty = 'returned_dirty';
    case DirtyZoneWashing = 'dirty_zone_washing';
    case DirtyZoneDrying = 'dirty_zone_drying';
    case CleanlinessCheckPending = 'cleanliness_check_pending';

    // --- Zona Bersih ---
    case CleanPendingPack = 'clean_pending_pack';
    case Packaging = 'packaging';
    case Sterilizing = 'sterilizing';
    case SterileCheckPending = 'sterile_check_pending';

    // --- Siap Distribusi ---
    case InStorage = 'in_storage';
    case ReadyForPickup = 'ready_for_pickup';

    // --- Di tangan unit ---
    case PickedUp = 'picked_up';
    case InUse = 'in_use';

    // --- Akhir hidup satu generasi barcode ---
    case Superseded = 'superseded';
    case Lost = 'lost';
    case Retired = 'retired';

    public function label(): string
    {
        return match ($this) {
            self::ReturnedDirty => 'Diterima CSSD (Kotor)',
            self::DirtyZoneWashing => 'Proses Pencucian',
            self::DirtyZoneDrying => 'Proses Pengeringan',
            self::CleanlinessCheckPending => 'Menunggu Cek Kebersihan',
            self::CleanPendingPack => 'Bersih, Menunggu Pengemasan',
            self::Packaging => 'Pengemasan & Pelabelan',
            self::Sterilizing => 'Proses Sterilisasi',
            self::SterileCheckPending => 'Menunggu Cek Label Steril',
            self::InStorage => 'Tersimpan di Gudang Steril',
            self::ReadyForPickup => 'Siap Diambil',
            self::PickedUp => 'Sudah Diambil Unit',
            self::InUse => 'Sedang Dipakai',
            self::Superseded => 'Barcode Diganti',
            self::Lost => 'Hilang',
            self::Retired => 'Tidak Dipakai Lagi',
        };
    }

    /** Zona yang ditampilkan ke unit — unit cukup tahu 3 kelompok besar, bukan 15 status. */
    public function zone(): ZoneBucket
    {
        return match ($this) {
            self::ReturnedDirty, self::DirtyZoneWashing,
            self::DirtyZoneDrying, self::CleanlinessCheckPending => ZoneBucket::Dirty,

            self::CleanPendingPack, self::Packaging,
            self::Sterilizing, self::SterileCheckPending => ZoneBucket::Clean,

            self::InStorage, self::ReadyForPickup => ZoneBucket::ReadyForDistribution,

            self::PickedUp, self::InUse => ZoneBucket::AtUnit,

            self::Superseded, self::Lost, self::Retired => ZoneBucket::Closed,
        };
    }

    public function colorClasses(): string
    {
        return match ($this) {
            self::Lost => 'bg-red-50 text-red-700 ring-red-200',
            self::Superseded, self::Retired => 'bg-slate-100 text-slate-500 ring-slate-200',
            default => $this->zone()->colorClasses(),
        };
    }

    /** Batch masih hidup di alur (bukan status penutup). */
    public function isActive(): bool
    {
        return ! in_array($this, [self::Superseded, self::Lost, self::Retired], true);
    }

    /**
     * Peta transisi yang diizinkan. Sengaja eksplisit supaya scan di stasiun
     * yang salah (mis. alat kotor discan di stasiun cek steril) ditolak tegas —
     * ini konteks keselamatan pasien, tidak boleh gagal diam-diam.
     *
     * @return array<int, self>
     */
    public function allowedNext(): array
    {
        return match ($this) {
            self::ReturnedDirty => [self::DirtyZoneWashing],
            self::DirtyZoneWashing => [self::DirtyZoneDrying],
            self::DirtyZoneDrying => [self::CleanlinessCheckPending],
            // Lolos -> lanjut zona bersih. Gagal -> cuci ulang.
            self::CleanlinessCheckPending => [self::CleanPendingPack, self::DirtyZoneWashing],
            self::CleanPendingPack => [self::Packaging],
            self::Packaging => [self::Sterilizing],
            self::Sterilizing => [self::SterileCheckPending],
            // Lolos -> simpan. Gagal -> kemas ulang atau cuci ulang (petugas yang menilai).
            self::SterileCheckPending => [self::InStorage, self::Packaging, self::DirtyZoneWashing],
            self::InStorage => [self::ReadyForPickup],
            self::ReadyForPickup => [self::PickedUp],
            // Unit bisa langsung mengembalikan tanpa sempat memakai ("gausah discan").
            self::PickedUp => [self::InUse, self::ReturnedDirty],
            self::InUse => [self::ReturnedDirty],
            self::Superseded, self::Lost, self::Retired => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedNext(), true);
    }

    /** Status yang boleh dipakai Admin sebagai koreksi/penutupan manual. */
    public static function adminOverrideTargets(): array
    {
        return [self::Lost, self::Retired];
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $s) => [$s->value => $s->label()])
            ->all();
    }
}
