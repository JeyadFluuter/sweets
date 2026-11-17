<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'phone' => '09'.fake()->randomElement(['1', '2', '3', '4', '5']).fake()->unique()->numerify('#######'),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'is_guest' => false,
            'is_blocked' => false,
        ];
    }

    public function guest(): static
    {
        return $this->state(fn () => [
            'name' => 'ضيف',
            'phone' => null,
            'email' => null,
            'password' => null,
            'is_guest' => true,
        ]);
    }

    public function blocked(): static
    {
        return $this->state(fn () => ['is_blocked' => true]);
    }
}
