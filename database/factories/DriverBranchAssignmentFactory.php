<?php

namespace Database\Factories;

use App\Enums\DriverAssignmentStatus;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\DriverBranchAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DriverBranchAssignment>
 */
class DriverBranchAssignmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'driver_id' => User::factory()->state(['user_type' => UserType::DRIVER, 'branch_id' => null]),
            'branch_id' => Branch::factory(),
            'status' => DriverAssignmentStatus::Active,
            'assigned_by' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['status' => DriverAssignmentStatus::Inactive]);
    }

    public function suspended(): static
    {
        return $this->state(fn (): array => ['status' => DriverAssignmentStatus::Suspended]);
    }
}
