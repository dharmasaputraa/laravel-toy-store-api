<?php

namespace Database\Factories;

use App\Models\UserAddress;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserAddress>
 */
class UserAddressFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'label' => fake()->randomElement(['Home', 'Office', 'Apartment', 'Warehouse']),
            'recipient_name' => fake()->name(),
            'phone' => fake()->phoneNumber(),
            'province_id' => fake()->numberBetween(1, 34),
            'city_id' => fake()->numberBetween(1, 100),
            'district' => fake()->streetName(),
            'postal_code' => fake()->postcode(),
            'full_address' => fake()->address(),
            'is_default' => fake()->boolean(),
        ];
    }

    /**
     * Indicate that the address is default.
     */
    public function default(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_default' => true,
        ]);
    }

    /**
     * Indicate that the address is not default.
     */
    public function notDefault(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_default' => false,
        ]);
    }
}
