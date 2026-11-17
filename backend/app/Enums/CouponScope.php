<?php

namespace App\Enums;

enum CouponScope: string
{
    case All = 'all';
    case Categories = 'categories';
    case Products = 'products';

    public function label(): string
    {
        return match ($this) {
            self::All => 'الكل',
            self::Categories => 'أقسام محددة',
            self::Products => 'منتجات محددة',
        };
    }
}
