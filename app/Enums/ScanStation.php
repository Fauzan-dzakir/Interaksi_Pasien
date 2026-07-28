<?php

namespace App\Enums;

/**
 * Stasiun scan fisik di CSSD. Satu scan = satu perpindahan tahap.
 *
 * Jalur kegagalan yang butuh penilaian petugas (mis. gagal cek label steril:
 * kemas ulang atau cuci ulang?) tidak dijadikan stasiun cepat, melainkan
 * tombol di halaman detail alat — kecuali "Cuci Ulang" yang memang sering
 * dipakai massal saat satu tray dinyatakan tidak bersih.
 */
enum ScanStation: string
{
    case Washing = 'washing';
    case Drying = 'drying';
    case CleanlinessCheck = 'cleanliness_check';
    case CleanlinessPass = 'cleanliness_pass';
    case Rewash = 'rewash';
    case Packaging = 'packaging';
    case Sterilizing = 'sterilizing';
    case SterileCheck = 'sterile_check';
    case StoragePass = 'storage_pass';

    public function label(): string
    {
        return match ($this) {
            self::Washing => 'Pencucian',
            self::Drying => 'Pengeringan',
            self::CleanlinessCheck => 'Cek Kebersihan',
            self::CleanlinessPass => 'Lolos Cek Kebersihan',
            self::Rewash => 'Cuci Ulang (Tidak Bersih)',
            self::Packaging => 'Pengemasan & Pelabelan',
            self::Sterilizing => 'Sterilisasi',
            self::SterileCheck => 'Cek Label Steril',
            self::StoragePass => 'Lolos Cek Steril → Gudang',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Washing => 'Alat masuk perendaman / washer-disinfector',
            self::Drying => 'Alat masuk drying cabinet atau dilap manual',
            self::CleanlinessCheck => 'Alat menunggu pemeriksaan kebersihan',
            self::CleanlinessPass => 'Alat dinyatakan bersih, lanjut ke Zona Bersih',
            self::Rewash => 'Alat dikembalikan untuk dicuci ulang',
            self::Packaging => 'Alat dikemas pouch / Tyvek dan diberi label',
            self::Sterilizing => 'Alat masuk autoclave atau mesin gas',
            self::SterileCheck => 'Alat menunggu pemeriksaan perubahan label indikator',
            self::StoragePass => 'Indikator berubah benar, alat masuk gudang steril',
        };
    }

    public function targetStatus(): ItemBatchStatus
    {
        return match ($this) {
            self::Washing, self::Rewash => ItemBatchStatus::DirtyZoneWashing,
            self::Drying => ItemBatchStatus::DirtyZoneDrying,
            self::CleanlinessCheck => ItemBatchStatus::CleanlinessCheckPending,
            self::CleanlinessPass => ItemBatchStatus::CleanPendingPack,
            self::Packaging => ItemBatchStatus::Packaging,
            self::Sterilizing => ItemBatchStatus::Sterilizing,
            self::SterileCheck => ItemBatchStatus::SterileCheckPending,
            self::StoragePass => ItemBatchStatus::InStorage,
        };
    }

    public function zone(): ZoneBucket
    {
        return $this->targetStatus()->zone();
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $s) => [$s->value => $s->label()])
            ->all();
    }
}
