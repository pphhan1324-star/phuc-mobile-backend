<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserAddress>
 */
class UserAddressFactory extends Factory
{
    protected $model = UserAddress::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'receiver_name' => $this->faker->name(),
            'receiver_phone' => $this->faker->phoneNumber(),
            'province_id' => $this->faker->numberBetween(1, 100),
            'district_id' => $this->faker->numberBetween(1, 500),
            'ward_id' => $this->faker->numberBetween(1, 1000),
            'address_detail' => $this->faker->streetAddress(),
            'is_default' => false,
            'type' => $this->faker->randomElement(['home', 'office', 'other']),
        ];
    }

    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }
}
