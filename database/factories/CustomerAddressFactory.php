<?php

namespace Database\Factories;

use App\Enums\AustralianState;
use App\Enums\CustomerAddressType;
use App\Enums\UserType;
use App\Models\CustomerAddress;
use App\Models\User;
use App\Support\Phone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerAddress>
 */
class CustomerAddressFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['user_type' => UserType::CUSTOMER, 'branch_id' => null]),
            'type' => CustomerAddressType::Shipping,
            'name' => null,
            'address' => fake()->streetAddress(),
            'region' => fake()->randomElement(AustralianState::cases()),
            'city' => fake()->city(),
            'zip' => fake()->numerify('####'),
            'email' => null,
            'phone' => null,
        ];
    }

    public function shipping(): static
    {
        return $this->state(fn (): array => [
            'type' => CustomerAddressType::Shipping,
            'name' => null,
            'email' => null,
            'phone' => null,
        ]);
    }

    public function billing(): static
    {
        return $this->state(fn (): array => [
            'type' => CustomerAddressType::Billing,
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => Phone::toE164(sprintf('0412%06d', fake()->numberBetween(1, 999999))),
        ]);
    }
}
