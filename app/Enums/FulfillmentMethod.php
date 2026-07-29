<?php

namespace App\Enums;

enum FulfillmentMethod: string
{
    /** Unit datang mengambil sendiri ke CSSD. */
    case Pickup = 'pickup';

    /** CSSD mengantar ke unit, dipakai terutama untuk IBS. */
    case Delivery = 'delivery';

    public function label(): string
    {
        return match ($this) {
            self::Pickup => 'Diambil Unit',
            self::Delivery => 'Dikirim CSSD',
        };
    }

    /** Status pesanan setelah CSSD selesai menyiapkan. */
    public function readyOrderStatus(): OrderStatus
    {
        return match ($this) {
            self::Pickup => OrderStatus::ReadyForPickup,
            self::Delivery => OrderStatus::Delivering,
        };
    }

    /** Status aset setelah CSSD selesai menyiapkan. */
    public function readyAssetStatus(): AssetStatus
    {
        return match ($this) {
            self::Pickup => AssetStatus::ReadyForHandover,
            self::Delivery => AssetStatus::InTransit,
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
