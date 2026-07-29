<?php

namespace App\Enums;

/** Metode sterilisasi, mengikuti pilihan pada formulir kertas CSSD. */
enum SterilizationMethod: string
{
    /** Uap bertekanan, dikemas pouch kertas-plastik medis. */
    case Steam = 'steam';

    /** Plasma hidrogen peroksida, dikemas plastik Tyvek. */
    case Plasma = 'plasma';

    case Formalin = 'formalin';

    /** Etilen Oksida, dikemas plastik Tyvek. */
    case Eto = 'eto';

    public function label(): string
    {
        return match ($this) {
            self::Steam => 'Steam (Autoclave)',
            self::Plasma => 'Plasma',
            self::Formalin => 'Formalin',
            self::Eto => 'Ethylene Oxide',
        };
    }

    /** Label ringkas untuk checkbox pada laporan PDF. */
    public function shortLabel(): string
    {
        return match ($this) {
            self::Steam => 'STEAM',
            self::Plasma => 'PLASMA',
            self::Formalin => 'FORMALIN',
            self::Eto => 'ETHYLENE OXIDE',
        };
    }

    public function packaging(): string
    {
        return match ($this) {
            self::Steam => 'Pouch kertas-plastik medis',
            self::Plasma, self::Eto => 'Plastik Tyvek',
            self::Formalin => 'Sesuai SOP formalin',
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
