<?php

namespace Database\Factories;

use App\Enums\DriverRosterStatus;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\DriverRoster;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DriverRoster>
 */
class DriverRosterFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'driver_id' => User::factory()->state(['user_type' => UserType::DRIVER, 'branch_id' => null]),
            'branch_id' => Branch::factory(),
            'date' => fake()->dateTimeBetween('now', '+2 weeks')->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '17:00',
            'status' => DriverRosterStatus::Scheduled,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (): array => ['status' => DriverRosterStatus::Completed]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (): array => ['status' => DriverRosterStatus::Cancelled]);
    }
}
