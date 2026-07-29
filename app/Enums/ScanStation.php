<?php

namespace App\Enums;

/**
 * Stasiun scan fisik di CSSD pada alur baru. Satu scan = satu perpindahan tahap.
 *
 * Tahap "Scan Barcode Baru" TIDAK ada di sini karena butuh input tambahan
 * (barcode baru, foto set, checklist isi set) sehingga punya halaman sendiri.
 */
enum ScanStation: string
{
    case ReceiveDirty = 'receive_dirty';
    case Washing = 'washing';
    case Sterilizing = 'sterilizing';
    case Complete = 'complete';

    public function label(): string
    {
        return match ($this) {
            self::ReceiveDirty => 'Terima Alat Kotor',
            self::Washing => 'Mulai Pencucian',
            self::Sterilizing => 'Masuk Sterilisasi',
            self::Complete => 'Selesai, Kembali ke Gudang',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::ReceiveDirty => 'Konfirmasi alat kotor dari unit sudah diterima CSSD',
            self::Washing => 'Alat masuk perendaman, washer-disinfector, atau pembersihan manual',
            self::Sterilizing => 'Alat yang sudah dikemas & berbarcode baru masuk autoclave/mesin gas',
            self::Complete => 'Sterilisasi selesai, alat kembali ke gudang steril atau ke batch unit',
        };
    }

    public function targetStatus(): AssetStatus
    {
        return match ($this) {
            self::ReceiveDirty => AssetStatus::ReturnPending,
            self::Washing => AssetStatus::Washing,
            self::Sterilizing => AssetStatus::Sterilizing,
            self::Complete => AssetStatus::Available,
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
