<?php

namespace Database\Factories;

use App\Enums\DriverAvailabilityStatus;
use App\Enums\DriverVehicleType;
use App\Enums\UserType;
use App\Models\DriverProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DriverProfile>
 */
class DriverProfileFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['user_type' => UserType::DRIVER, 'branch_id' => null]),
            'license_number' => fake()->unique()->bothify('DL-#####??'),
            'vehicle_type' => fake()->randomElement(DriverVehicleType::cases()),
            'availability_status' => DriverAvailabilityStatus::Offline,
            'notes' => null,
            'address' => null,
            'latitude' => null,
            'longitude' => null,
            'vehicle_image_path' => null,
        ];
    }

    public function available(): static
    {
        return $this->state(fn (): array => ['availability_status' => DriverAvailabilityStatus::Available]);
    }

    public function locatedAt(float $latitude, float $longitude): static
    {
        return $this->state(fn (): array => ['latitude' => $latitude, 'longitude' => $longitude]);
    }
}
