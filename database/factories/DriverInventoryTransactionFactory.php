<?php

namespace Database\Factories;

use App\Enums\InventoryTransactionType;
use App\Models\Branch;
use App\Models\DriverInventoryTransaction;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DriverInventoryTransaction>
 */
class DriverInventoryTransactionFactory extends Factory
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
            'user_id' => User::factory(),
            'product_id' => Product::factory(),
            'variant_id' => null,
            'type' => InventoryTransactionType::DriverAssign,
            'reason' => null,
            'reason_note' => null,
            'quantity' => $delta,
            'before_quantity' => $before,
            'after_quantity' => $before + $delta,
            'reference_type' => null,
            'reference_id' => null,
            'created_by' => null,
            'created_at' => now(),
        ];
    }
}
