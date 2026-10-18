<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Address>
 */
class AddressFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => 'home',
            'receiver_name' => fake()->name(),
            'phone' => '04'.fake()->numerify('########'),
            'address' => fake()->streetAddress(),
            'city' => 'Sydney',
            'postal_code' => '2000',
            'latitude' => -33.8688,
            'longitude' => 151.2093,
        ];
    }
}
