<?php

namespace App\Enums;

enum BannerLinkType: string
{
    case Product = 'product';
    case Category = 'category';
    case Collection = 'collection';
    case Coupon = 'coupon';
    case None = 'none';

    public function label(): string
    {
        return match ($this) {
            self::Product => 'منتج',
            self::Category => 'قسم',
            self::Collection => 'باقة',
            self::Coupon => 'كوبون',
            self::None => 'بدون رابط',
        };
    }
}
