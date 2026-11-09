<?php

namespace Database\Factories;

use App\Models\DriverLocation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DriverLocation>
 */
class DriverLocationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'driver_id' => User::factory(),
            'fulfillment_id' => null,
            'latitude' => -33.8688,
            'longitude' => 151.2093,
            'accuracy' => 12.0,
            'speed' => null,
            'heading' => null,
            'recorded_at' => now(),
        ];
    }
}
