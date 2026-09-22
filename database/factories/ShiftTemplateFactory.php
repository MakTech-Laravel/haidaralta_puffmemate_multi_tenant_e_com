<?php

namespace Database\Factories;

use App\Enums\ShiftAudience;
use App\Enums\ShiftOwnerType;
use App\Models\Branch;
use App\Models\ShiftTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShiftTemplate>
 */
class ShiftTemplateFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'owner_type' => ShiftOwnerType::Platform,
            'branch_id' => null,
            'name' => fake()->unique()->words(2, true).' Shift',
            'audience' => ShiftAudience::All,
            'start_time' => '08:00',
            'end_time' => '17:00',
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

    public function forStaff(): static
    {
        return $this->state(fn (): array => ['audience' => ShiftAudience::Staff]);
    }

    public function forDrivers(): static
    {
        return $this->state(fn (): array => ['audience' => ShiftAudience::Driver]);
    }
}
