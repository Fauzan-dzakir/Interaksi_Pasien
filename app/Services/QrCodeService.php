<?php

namespace App\Services;

use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;

class QrCodeService
{
    /**
     * Menghasilkan QR sebagai SVG inline.
     *
     * SVG dipilih (bukan PNG) supaya label tetap tajam di berbagai ukuran cetak
     * dan tidak bergantung pada ekstensi GD/Imagick di server RS.
     *
     * Error correction HIGH: label sterilisasi kerap terkena panas, uap, dan
     * gesekan — QR tetap terbaca walau sebagian permukaannya rusak.
     */
    public function svg(string $data, int $size = 160): string
    {
        $qrCode = new QrCode(
            data: $data,
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: $size,
            margin: 0,
        );

        return (new SvgWriter)->write($qrCode)->getString();
    }

    /** SVG siap ditempel ke atribut src gambar. */
    public function dataUri(string $data, int $size = 160): string
    {
        return 'data:image/svg+xml;base64,'.base64_encode($this->svg($data, $size));
    }
}
