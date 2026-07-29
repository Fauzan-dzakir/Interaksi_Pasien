<?php

namespace App\Enums;

/**
 * Kategori sensitivitas bahan alat, menentukan jalur pembersihan di Zona Kotor.
 *
 * Catatan: nilai ini bersifat INFORMATIF (panduan SOP untuk petugas), sistem tidak
 * mengunci/memblokir metode cuci yang dipilih petugas, keputusan tetap di tangan petugas CSSD.
 */
enum MaterialSensitivity: string
{
    case HeatWaterResistant = 'heat_water_resistant';
    case HeatSensitiveWaterResistant = 'heat_sensitive_water_resistant';
    case HeatSensitiveWaterSensitive = 'heat_sensitive_water_sensitive';

    public function label(): string
    {
        return match ($this) {
            self::HeatWaterResistant => 'Tahan panas & tahan uap air',
            self::HeatSensitiveWaterResistant => 'Sensitif panas & tahan air',
            self::HeatSensitiveWaterSensitive => 'Sensitif panas & sensitif air',
        };
    }

    /** Metode pembersihan yang disarankan di Zona Kotor. */
    public function cleaningMethod(): string
    {
        return match ($this) {
            self::HeatWaterResistant => 'Direndam, pembersihan mekanis, air panas (washer-disinfector)',
            self::HeatSensitiveWaterResistant => 'Direndam, dibersihkan manual, dibilas air RO (washer-disinfector)',
            self::HeatSensitiveWaterSensitive => 'Diusap & dibasahi cairan disinfektan khusus',
        };
    }

    /** Metode pengeringan yang disarankan sebelum masuk Zona Bersih. */
    public function dryingMethod(): string
    {
        return match ($this) {
            self::HeatWaterResistant => 'Drying cabinet suhu tinggi',
            self::HeatSensitiveWaterResistant => 'Drying cabinet suhu rendah',
            self::HeatSensitiveWaterSensitive => 'Manual dengan lap',
        };
    }

    /** @return array<string, string> value => label, untuk dropdown form. */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $s) => [$s->value => $s->label()])
            ->all();
    }
}
