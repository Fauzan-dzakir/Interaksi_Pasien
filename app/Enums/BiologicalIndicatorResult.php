<?php

namespace App\Enums;

/**
 * Hasil uji indikator biologi pada formulir sterilisasi.
 * Hasil "Tidak Baik" berarti muatan tersebut tidak boleh dipakai ke pasien.
 */
enum BiologicalIndicatorResult: string
{
    case Pending = 'pending';
    case Baik = 'baik';
    case TidakBaik = 'tidak_baik';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu Hasil',
            self::Baik => 'Baik',
            self::TidakBaik => 'Tidak Baik',
        };
    }

    public function colorClasses(): string
    {
        return match ($this) {
            self::Pending => 'bg-amber-50 text-amber-700 ring-amber-200',
            self::Baik => 'bg-leaf-50 text-leaf-700 ring-leaf-200',
            self::TidakBaik => 'bg-red-50 text-red-700 ring-red-200',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $r) => [$r->value => $r->label()])
            ->all();
    }
}
