<?php

namespace Database\Factories;

use App\Enums\ShiftAssignmentStatus;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\DriverRoster;
use App\Models\ShiftTemplate;
use App\Models\User;
use App\Models\UserShiftAssignment;
use App\Services\Workforce\AssignShiftService;
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
        return [
            'user_id' => User::factory()->state(['user_type' => UserType::DRIVER, 'branch_id' => null]),
            'branch_id' => Branch::factory(),
            'shift_template_id' => ShiftTemplate::factory(),
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->endOfMonth()->toDateString(),
            'days_of_week' => DriverRoster::DAYS_ALL,
            'status' => ShiftAssignmentStatus::Scheduled,
            'assigned_by' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (UserShiftAssignment $assignment): void {
            app(AssignShiftService::class)->syncDays($assignment);
        });
    }

    public function cancelled(): static
    {
        return $this->state(fn (): array => ['status' => ShiftAssignmentStatus::Cancelled]);
    }
}
