<?php

namespace App\Enums;

enum UnitType: string
{
    /** Instalasi Bedah Sentral / Kamar Operasi. */
    case IbsOk = 'ibs_ok';

    /** Ruang rawat inap / rawat jalan / poli. */
    case Ward = 'ward';

    /** CSSD sendiri (unit pengelola sterilisasi). */
    case Cssd = 'cssd';

    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::IbsOk => 'IBS / Kamar Operasi',
            self::Ward => 'Ruang Rawat',
            self::Cssd => 'CSSD',
            self::Other => 'Lainnya',
        };
    }

    /** @return array<string, string> value => label, untuk dropdown form. */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type) => [$type->value => $type->label()])
            ->all();
    }
}
