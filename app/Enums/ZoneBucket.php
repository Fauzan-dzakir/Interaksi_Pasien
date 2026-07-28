<?php

namespace App\Enums;

/**
 * Pengelompokan zona yang ditampilkan ke unit pengirim.
 * Dihitung dari status (lihat ItemBatchStatus::zone()), bukan disimpan di kolom,
 * supaya hanya ada satu sumber kebenaran.
 */
enum ZoneBucket: string
{
    case Dirty = 'dirty';
    case Clean = 'clean';
    case ReadyForDistribution = 'ready';
    case AtUnit = 'at_unit';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Dirty => 'Zona Kotor',
            self::Clean => 'Zona Bersih',
            self::ReadyForDistribution => 'Siap Distribusi',
            self::AtUnit => 'Di Unit',
            self::Closed => 'Selesai / Ditutup',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Dirty => 'Perendaman, pencucian, pengeringan',
            self::Clean => 'Pengemasan, pelabelan, sterilisasi',
            self::ReadyForDistribution => 'Tersimpan & siap diambil unit',
            self::AtUnit => 'Sudah diambil / sedang dipakai unit',
            self::Closed => 'Barcode diganti, hilang, atau tidak dipakai lagi',
        };
    }

    public function colorClasses(): string
    {
        return match ($this) {
            self::Dirty => 'bg-amber-50 text-amber-700 ring-amber-200',
            self::Clean => 'bg-sky-50 text-sky-700 ring-sky-200',
            self::ReadyForDistribution => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            self::AtUnit => 'bg-violet-50 text-violet-700 ring-violet-200',
            self::Closed => 'bg-slate-100 text-slate-500 ring-slate-200',
        };
    }

    /** @return array<int, self> Zona yang dipantau unit di dashboard live tracking. */
    public static function trackedByUnit(): array
    {
        return [self::Dirty, self::Clean, self::ReadyForDistribution];
    }

    /** @return array<int, ItemBatchStatus> Status yang termasuk zona ini. */
    public function statuses(): array
    {
        return array_values(array_filter(
            ItemBatchStatus::cases(),
            fn (ItemBatchStatus $s) => $s->zone() === $this,
        ));
    }
}
