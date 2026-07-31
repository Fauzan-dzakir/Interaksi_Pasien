<?php

namespace App\Enums;

enum SterilizationMethod: string
{
    /** Uap bertekanan — dikemas pouch kertas-plastik medis. */
    case Autoclave = 'autoclave';

    /** Plasma — dikemas plastik Tyvek. */
    case Plasma = 'plasma';

    /** Etilen Oksida (EO) — dikemas plastik Tyvek. */
    case Gas = 'gas';

    public function label(): string
    {
        return match ($this) {
            self::Autoclave => 'Autoclave (Steam)',
            self::Plasma => 'Plasma (H2O2)',
            self::Gas => 'Etilen Oksida (EO Gas)',
        };
    }

    public function expirationMonths(): int
    {
        return match ($this) {
            self::Autoclave, self::Plasma => 6,
            self::Gas => 12,
        };
    }

    public function packaging(): string
    {
        return match ($this) {
            self::Autoclave => 'Pouch kertas-plastik medis',
            self::Plasma, self::Gas => 'Plastik Tyvek',
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
