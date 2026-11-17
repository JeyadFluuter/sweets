<?php

namespace Database\Factories;

use App\Enums\BannerLinkType;
use App\Models\Banner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Banner>
 */
class BannerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'link_type' => BannerLinkType::None,
            'link_id' => null,
            'sort_order' => fake()->numberBetween(0, 10),
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'is_active' => true,
        ];
    }
}
