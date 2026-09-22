<?php

namespace Database\Factories;

use App\Enums\ShiftDayKind;
use App\Models\Branch;
use App\Models\ShiftTemplate;
use App\Models\User;
use App\Models\UserShiftAssignment;
use App\Models\UserShiftDay;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserShiftDay>
 */
class UserShiftDayFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_shift_assignment_id' => UserShiftAssignment::factory(),
            'user_id' => User::factory(),
            'branch_id' => Branch::factory(),
            'duty_on' => now()->toDateString(),
            'shift_template_id' => ShiftTemplate::factory(),
            'kind' => ShiftDayKind::Scheduled,
        ];
    }

    public function leave(): static
    {
        return $this->state(fn (): array => [
            'shift_template_id' => null,
            'kind' => ShiftDayKind::Leave,
        ]);
    }

    public function weeklyOff(): static
    {
        return $this->state(fn (): array => [
            'shift_template_id' => null,
            'kind' => ShiftDayKind::WeeklyOff,
        ]);
    }
}
