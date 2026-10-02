<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\BranchArea;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BranchArea>
 */
class BranchAreaFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'name' => fake()->unique()->words(2, true),
            'address' => fake()->streetAddress(),
            'latitude' => fake()->latitude(-34.0, -33.5),
            'longitude' => fake()->longitude(150.5, 151.5),
            'city' => fake()->city(),
            'state' => fake()->state(),
            'postal_code' => fake()->optional()->postcode(),
            'notes' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'is_active' => false,
        ]);
    }
}
