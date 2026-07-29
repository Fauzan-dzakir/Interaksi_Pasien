<?php

namespace App\Enums;

enum AssetType: string
{
    /** Satu set alat yang dirakit, satu barcode mewakili seluruh isi set. */
    case Set = 'set';

    /** Satu alat lepasan, satu barcode untuk satu alat. */
    case Item = 'item';

    public function label(): string
    {
        return match ($this) {
            self::Set => 'Set Alat',
            self::Item => 'Alat Satuan',
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
