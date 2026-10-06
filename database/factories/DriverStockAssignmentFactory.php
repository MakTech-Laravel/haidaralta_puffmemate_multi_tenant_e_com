<?php

namespace Database\Factories;

use App\Enums\DriverStockAssignmentMode;
use App\Models\Branch;
use App\Models\DriverStockAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DriverStockAssignment>
 */
class DriverStockAssignmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'assigned_by' => User::factory(),
            'mode' => DriverStockAssignmentMode::Single,
        ];
    }
}
