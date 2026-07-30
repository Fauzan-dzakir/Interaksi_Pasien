<?php

namespace App\Enums;

enum BatchType: string
{
    /** Satu set alat yang dirakit jadi satu — satu QR mewakili seluruh set. */
    case Set = 'set';

    /** Alat lepasan sejenis yang dikemas bersama — satu QR mewakili sejumlah pcs. */
    case Individual = 'individual';

    public function label(): string
    {
        return match ($this) {
            self::Set => 'Per Set',
            self::Individual => 'Per Barang',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $t) => [$t->value => $t->label()])
            ->all();
    }
}
