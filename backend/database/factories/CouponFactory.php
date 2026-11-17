<?php

namespace Database\Factories;

use App\Enums\CouponScope;
use App\Enums\CouponType;
use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Coupon>
 */
class CouponFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => Str::upper(Str::random(8)),
            'type' => CouponType::Percent,
            'value' => 10,
            'max_discount' => 20,
            'min_order_amount' => 0,
            'usage_limit' => 100,
            'usage_limit_per_customer' => 1,
            'used_count' => 0,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'scope' => CouponScope::All,
            'is_active' => true,
        ];
    }
}
