<?php

namespace App\Enums;

enum SterilizationMethod: string
{
    /** Uap bertekanan — dikemas pouch kertas-plastik medis. */
    case Autoclave = 'autoclave';

    /** Plasma / Etilen Oksida — dikemas plastik Tyvek. */
    case Gas = 'gas';

    public function label(): string
    {
        return match ($this) {
            self::Autoclave => 'Autoclave (Steam)',
            self::Gas => 'Plasma / EtO (Gas)',
        };
    }

    public function packaging(): string
    {
        return match ($this) {
            self::Autoclave => 'Pouch kertas-plastik medis',
            self::Gas => 'Plastik Tyvek',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $m) => [$m->value => $m->label()])
            ->all();
    }
}
