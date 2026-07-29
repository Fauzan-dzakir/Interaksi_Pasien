<?php

namespace App\Enums;

/**
 * Status pesanan cuci alat oleh unit.
 *
 * Arah alurnya seperti jasa laundry: unit mengirim alat kotor beserta foto,
 * CSSD memproses, lalu alat steril dikembalikan ke unit.
 */
enum OrderStatus: string
{
    /** Unit sudah membuat pesanan, alat kotor belum diterima CSSD. */
    case Pending = 'pending';

    /** CSSD sudah menerima alat dan sedang memprosesnya. */
    case Preparing = 'preparing';

    /** Seluruh alat selesai steril, siap diambil unit di loket CSSD. */
    case ReadyForPickup = 'ready_for_pickup';

    /** CSSD sedang mengantar alat steril kembali ke unit. */
    case Delivering = 'delivering';

    /** Alat sudah kembali di unit, pesanan selesai. */
    case Received = 'received';

    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu Diterima CSSD',
            self::Preparing => 'Sedang Diproses CSSD',
            self::ReadyForPickup => 'Selesai, Siap Diambil',
            self::Delivering => 'Selesai, Sedang Diantar',
            self::Received => 'Sudah Kembali ke Unit',
            self::Cancelled => 'Dibatalkan',
        };
    }

    public function colorClasses(): string
    {
        return match ($this) {
            self::Pending => 'bg-amber-50 text-amber-700 ring-amber-200',
            self::Preparing => 'bg-sky-50 text-sky-700 ring-sky-200',
            self::ReadyForPickup => 'bg-leaf-50 text-leaf-700 ring-leaf-200',
            self::Delivering => 'bg-violet-50 text-violet-700 ring-violet-200',
            self::Received => 'bg-slate-100 text-slate-600 ring-slate-200',
            self::Cancelled => 'bg-slate-100 text-slate-500 ring-slate-200',
        };
    }

    public function isOpen(): bool
    {
        return ! in_array($this, [self::Received, self::Cancelled], true);
    }

    /** Pesanan masih boleh dibatalkan unit selama alatnya belum diterima CSSD. */
    public function isEditable(): bool
    {
        return $this === self::Pending;
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $s) => [$s->value => $s->label()])
            ->all();
    }
}
