<?php

namespace Database\Factories;

use App\Enums\UserType;
use App\Models\Branch;
use App\Models\ShiftTemplate;
use App\Models\User;
use App\Models\UserShiftHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserShiftHistory>
 */
class UserShiftHistoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $template = ShiftTemplate::factory();

        return [
            'user_id' => User::factory()->state(['user_type' => UserType::DRIVER, 'branch_id' => null]),
            'branch_id' => Branch::factory(),
            'previous_shift_template_id' => null,
            'new_shift_template_id' => $template,
            'previous_start_time' => null,
            'previous_end_time' => null,
            'new_start_time' => '08:00',
            'new_end_time' => '17:00',
            'effective_date' => now()->toDateString(),
            'reason' => fake()->optional()->sentence(),
            'note' => null,
            'changed_by' => null,
        ];
    }
}
