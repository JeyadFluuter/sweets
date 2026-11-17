<?php

namespace App\Enums;

enum DeliveryType: string
{
    case Pickup = 'pickup';
    case Delivery = 'delivery';

    public function label(): string
    {
        return match ($this) {
            self::Pickup => 'استلام من المعمل',
            self::Delivery => 'توصيل',
        };
    }
}
