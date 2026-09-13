<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Inventory;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Inventory>
 */
class InventoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->numberBetween(0, 100);
        $reserved = fake()->numberBetween(0, min(10, $quantity));

        return [
            'branch_id' => Branch::factory(),
            'product_id' => Product::factory(),
            'variant_id' => null,
            'quantity' => $quantity,
            'reserved_quantity' => $reserved,
            'available_quantity' => $quantity - $reserved,
            'minimum_stock' => 5,
            'status' => true,
        ];
    }
}
