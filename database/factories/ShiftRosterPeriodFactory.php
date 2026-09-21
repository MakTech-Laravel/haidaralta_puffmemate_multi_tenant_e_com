<?php

namespace Database\Factories;

use App\Enums\ShiftOwnerType;
use App\Models\Branch;
use App\Models\ShiftRosterPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShiftRosterPeriod>
 */
class ShiftRosterPeriodFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'owner_type' => ShiftOwnerType::Platform,
            'branch_id' => null,
            'name' => fake()->unique()->monthName().' Roster',
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->endOfMonth()->toDateString(),
            'is_active' => true,
            'created_by' => null,
        ];
    }

    public function forBranch(Branch $branch): static
    {
        return $this->state(fn (): array => [
            'owner_type' => ShiftOwnerType::Branch,
            'branch_id' => $branch->id,
        ]);
    }
}
