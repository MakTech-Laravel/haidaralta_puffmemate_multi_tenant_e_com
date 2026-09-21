<?php

namespace Database\Factories;

use App\Enums\TransferSubjectType;
use App\Enums\TransferType;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\User;
use App\Models\UserTransferHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserTransferHistory>
 */
class UserTransferHistoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['user_type' => UserType::DRIVER, 'branch_id' => null]),
            'subject_type' => TransferSubjectType::Driver,
            'transfer_type' => TransferType::BranchChange,
            'from_branch_id' => Branch::factory(),
            'to_branch_id' => Branch::factory(),
            'from_role' => 'driver',
            'to_role' => 'driver',
            'effective_date' => now()->toDateString(),
            'reason' => fake()->optional()->sentence(),
            'transferred_by' => null,
        ];
    }

    public function staff(): static
    {
        return $this->state(fn (): array => [
            'user_id' => User::factory()->state(['user_type' => UserType::BRANCH]),
            'subject_type' => TransferSubjectType::BranchStaff,
            'from_role' => 'staff',
            'to_role' => 'staff',
        ]);
    }

    public function roleChange(): static
    {
        return $this->staff()->state(function (array $attributes): array {
            $branchId = $attributes['from_branch_id'] ?? Branch::factory();

            return [
                'transfer_type' => TransferType::RoleChange,
                'to_branch_id' => $branchId,
                'from_role' => 'staff',
                'to_role' => 'manager',
            ];
        });
    }
}
