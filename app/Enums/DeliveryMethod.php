<?php

namespace App\Enums;

enum DeliveryMethod: string
{
    /** Unit datang mengambil sendiri ke CSSD. */
    case UnitPickup = 'unit_pickup';

    /** CSSD mengantar langsung ke unit ("Dikirim Langsung" pada alur). */
    case DirectDelivery = 'direct_delivery';

    public function label(): string
    {
        return match ($this) {
            self::UnitPickup => 'Diambil Unit',
            self::DirectDelivery => 'Dikirim Langsung oleh CSSD',
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
