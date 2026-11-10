<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductPackage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductPackage>
 */
class ProductPackageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->randomElement([1, 2, 3, 5, 10]);

        return [
            'product_id' => Product::factory(),
            'label' => $quantity === 1 ? 'Single Item' : 'Pack of '.$quantity,
            'quantity' => $quantity,
            'price' => fake()->randomFloat(2, 10, 500),
            'status' => true,
            'sort_order' => 0,
        ];
    }

    public function disabled(): static
    {
        return $this->state(fn (): array => [
            'status' => false,
        ]);
    }
}
