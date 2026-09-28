<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\BranchShippingRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BranchShippingRule>
 */
class BranchShippingRuleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'base_fee' => 5,
            'free_delivery_distance_km' => 3,
            'cost_per_km' => 1.5,
            'minimum_delivery_charge' => 5,
            'status' => true,
        ];
    }
}
