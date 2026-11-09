<?php

namespace Database\Factories;

use App\Models\DeliveryShift;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeliveryShift>
 */
class DeliveryShiftFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true).' Delivery',
            'start_time' => '09:00',
            'end_time' => '17:00',
            'is_active' => true,
            'created_by' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'is_active' => false,
        ]);
    }
}
