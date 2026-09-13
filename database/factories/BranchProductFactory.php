<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\BranchProduct;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BranchProduct>
 */
class BranchProductFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'product_id' => Product::factory(),
            'status' => false,
        ];
    }

    public function enabled(): static
    {
        return $this->state(fn (): array => ['status' => true]);
    }

    public function disabled(): static
    {
        return $this->state(fn (): array => ['status' => false]);
    }
}
