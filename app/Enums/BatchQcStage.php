<?php

namespace App\Enums;

/**
 * Checklist QC per tahap untuk kartu "Pindahkan Tahap" di halaman detail alat.
 *
 * Ini dokumentasi/pembuktian TAMBAHAN — bukan pengganti Stasiun Scan. Alat tetap
 * bisa berpindah tahap lewat scan biasa tanpa checklist ini sama sekali; checklist
 * hanya muncul sebagai opsi di halaman detail alat saat status alat persis berada
 * di salah satu titik cek (atStatus()) di bawah.
 */
enum BatchQcStage: string
{
    case Penerimaan = 'penerimaan';
    case Dekontaminasi = 'dekontaminasi';
    case BersihPackaging = 'bersih_packaging';
    case Steril = 'steril';

    public function label(): string
    {
        return match ($this) {
            self::Penerimaan => 'Penerimaan',
            self::Dekontaminasi => 'Dekontaminasi',
            self::BersihPackaging => 'Bersih / Setting Packaging',
            self::Steril => 'Steril',
        };
    }

    /** Status alat saat ini di mana checklist tahap ini ditawarkan. */
    public function atStatus(): ItemBatchStatus
    {
        return match ($this) {
            self::Penerimaan => ItemBatchStatus::ReturnedDirty,
            self::Dekontaminasi => ItemBatchStatus::CleanlinessCheckPending,
            self::BersihPackaging => ItemBatchStatus::Packaging,
            self::Steril => ItemBatchStatus::SterileCheckPending,
        };
    }

    /** Tujuan bila seluruh item checklist "sesuai". */
    public function passTarget(): ItemBatchStatus
    {
        return match ($this) {
            self::Penerimaan => ItemBatchStatus::DirtyZoneWashing,
            self::Dekontaminasi => ItemBatchStatus::CleanPendingPack,
            self::BersihPackaging => ItemBatchStatus::Sterilizing,
            self::Steril => ItemBatchStatus::InStorage,
        };
    }

    /**
     * Pilihan tujuan bila ADA item checklist yang "tidak sesuai" — mengikuti
     * jalur gagal yang sudah ada di ItemBatchStatus::allowedNext(). Array kosong
     * berarti tidak ada tahap mundur yang relevan di sistem ini — alat tetap di
     * tahap sekarang, checklist hanya dicatat sebagai catatan untuk ditindaklanjuti
     * manual oleh petugas.
     *
     * @return array<int, ItemBatchStatus>
     */
    public function failTargets(): array
    {
        return match ($this) {
            self::Penerimaan => [],
            self::Dekontaminasi => [ItemBatchStatus::DirtyZoneWashing],
            self::BersihPackaging => [],
            self::Steril => [ItemBatchStatus::Packaging, ItemBatchStatus::DirtyZoneWashing],
        };
    }

    /** @return array<int, array{key: string, label: string}> */
    public function items(): array
    {
        return match ($this) {
            self::Penerimaan => [
                ['key' => 'serah_terima', 'label' => 'Serah terima alat'],
            ],
            self::Dekontaminasi => [
                ['key' => 'pemilahan_instrumen', 'label' => 'Pemilahan instrumen'],
                ['key' => 'uji_visual', 'label' => 'Uji visual'],
                ['key' => 'uji_fungsi_instrumen', 'label' => 'Uji fungsi instrumen'],
                ['key' => 'perendaman_sample_pencucian', 'label' => 'Perendaman sample pencucian'],
            ],
            self::BersihPackaging => [
                ['key' => 'uji_visual', 'label' => 'Uji visual'],
                ['key' => 'uji_bau_instrumen', 'label' => 'Uji bau instrumen'],
                ['key' => 'uji_fungsi_instrumen', 'label' => 'Uji fungsi instrumen'],
                ['key' => 'pelumasan_instrumen', 'label' => 'Pelumasan instrumen'],
                ['key' => 'pelumasan_bor', 'label' => 'Pelumasan bor'],
                ['key' => 'sorting_set_instrumen', 'label' => 'Sorting set instrumen'],
                ['key' => 'packing_sesuai_kemasan', 'label' => 'Packing instrumen sesuai kemasan'],
                ['key' => 'indikator_sesuai_mesin', 'label' => 'Indikator sesuai mesin yang digunakan'],
                ['key' => 'label', 'label' => 'Label'],
            ],
            self::Steril => [
                ['key' => 'uji_visual', 'label' => 'Uji visual'],
                ['key' => 'cek_hasil_print', 'label' => 'Cek hasil print'],
                ['key' => 'instrumen_disimpan', 'label' => 'Instrumen disimpan'],
                ['key' => 'instrumen_didistribusikan', 'label' => 'Instrumen didistribusikan'],
            ],
        };
    }

    /** Checklist yang berlaku untuk status alat saat ini, kalau ada. */
    public static function forStatus(ItemBatchStatus $status): ?self
    {
        foreach (self::cases() as $stage) {
            if ($stage->atStatus() === $status) {
                return $stage;
            }
        }

        return null;
    }
}
