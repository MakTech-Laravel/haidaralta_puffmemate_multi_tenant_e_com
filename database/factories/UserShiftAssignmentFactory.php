<?php

namespace Database\Factories;

use App\Enums\ShiftAssignmentStatus;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\DriverRoster;
use App\Models\ShiftRosterPeriod;
use App\Models\ShiftTemplate;
use App\Models\User;
use App\Models\UserShiftAssignment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserShiftAssignment>
 */
class UserShiftAssignmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $period = ShiftRosterPeriod::factory();

        return [
            'user_id' => User::factory()->state(['user_type' => UserType::DRIVER, 'branch_id' => null]),
            'branch_id' => Branch::factory(),
            'shift_template_id' => ShiftTemplate::factory(),
            'shift_roster_period_id' => $period,
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->endOfMonth()->toDateString(),
            'days_of_week' => DriverRoster::DAYS_ALL,
            'status' => ShiftAssignmentStatus::Scheduled,
            'assigned_by' => null,
        ];
    }

    public function cancelled(): static
    {
        return $this->state(fn (): array => ['status' => ShiftAssignmentStatus::Cancelled]);
    }
}
