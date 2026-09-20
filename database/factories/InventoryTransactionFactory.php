<?php

namespace Database\Factories;

use App\Enums\InventoryTransactionType;
use App\Models\Branch;
use App\Models\InventoryTransaction;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryTransaction>
 */
class InventoryTransactionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $before = fake()->numberBetween(0, 50);
        $delta = fake()->numberBetween(1, 10);

        return [
            'branch_id' => Branch::factory(),
            'product_id' => Product::factory(),
            'variant_id' => null,
            'type' => InventoryTransactionType::StockIn,
            'reason' => null,
            'reason_note' => null,
            'quantity' => $delta,
            'before_quantity' => $before,
            'after_quantity' => $before + $delta,
            'unit_cost' => fake()->optional(0.6)->randomFloat(2, 1, 40),
            'reference_type' => null,
            'reference_id' => null,
            'created_by' => null,
            'created_at' => now(),
        ];
    }
}
