<?php

namespace App\Enums;

/**
 * Dicatat murni untuk keperluan audit/penelusuran masalah.
 * Logika bisnis TIDAK boleh bercabang berdasarkan nilai ini —
 * kamera dan scanner fisik memanggil handler yang sama persis.
 */
enum ScanInputMethod: string
{
    case QrCamera = 'qr_camera';
    case HidScanner = 'hid_scanner';
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::QrCamera => 'Kamera',
            self::HidScanner => 'Scanner Barcode',
            self::Manual => 'Input Manual',
        };
    }
}
