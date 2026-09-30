<?php

namespace Database\Factories;

use App\Models\DriverStockAssignment;
use App\Models\DriverStockAssignmentItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DriverStockAssignmentItem>
 */
class DriverStockAssignmentItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'driver_stock_assignment_id' => DriverStockAssignment::factory(),
            'product_id' => Product::factory(),
            'variant_id' => null,
            'quantity' => fake()->numberBetween(1, 20),
        ];
    }
}
