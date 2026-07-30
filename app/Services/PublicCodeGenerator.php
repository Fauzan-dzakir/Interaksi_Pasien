<?php

namespace App\Services;

use App\Models\ItemBatch;
use RuntimeException;

/**
 * Membuat kode unik yang dicetak pada label QR, mis. CSSD-7F3K9M2P.
 *
 * Sengaja memakai kode pendek & buram (bukan URL) karena:
 *  - label tidak ikut basi kalau alamat/hostname server RS berubah,
 *  - lebih pendek = QR lebih renggang = lebih tahan saat label kena panas/lembap,
 *  - huruf/angka yang mudah tertukar (I, O, 0, 1) dibuang supaya aman saat
 *    petugas terpaksa mengetik manual ketika label rusak.
 */
class PublicCodeGenerator
{
    private const PREFIX = 'CSSD-';

    private const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    private const LENGTH = 8;

    public function generate(): string
    {
        // 32^8 kemungkinan; percobaan ulang menutup kemungkinan tabrakan acak.
        for ($attempt = 0; $attempt < 20; $attempt++) {
            $code = self::PREFIX.$this->randomString();

            if (! ItemBatch::where('public_code', $code)->exists()) {
                return $code;
            }
        }

        throw new RuntimeException('Gagal membuat kode unik untuk label QR setelah 20 percobaan.');
    }

    private function randomString(): string
    {
        $max = strlen(self::ALPHABET) - 1;
        $out = '';

        for ($i = 0; $i < self::LENGTH; $i++) {
            $out .= self::ALPHABET[random_int(0, $max)];
        }

        return $out;
    }

    /** Normalisasi hasil scan/ketik manual sebelum dicocokkan ke database. */
    public static function normalize(string $raw): string
    {
        return strtoupper(trim($raw));
    }
}
