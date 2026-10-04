<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\BranchArea;
use App\Models\BranchAreaTransferHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BranchAreaTransferHistory>
 */
class BranchAreaTransferHistoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'branch_area_id' => BranchArea::factory(),
            'from_branch_id' => Branch::factory(),
            'to_branch_id' => Branch::factory(),
            'effective_date' => now()->toDateString(),
            'reason' => fake()->sentence(),
            'transferred_by' => User::factory(),
        ];
    }
}
