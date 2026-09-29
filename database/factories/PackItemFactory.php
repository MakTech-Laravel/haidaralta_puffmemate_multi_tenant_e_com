<?php

namespace Database\Factories;

use App\Models\Pack;
use App\Models\PackItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PackItem>
 */
class PackItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pack_id' => Pack::factory(),
            'product_id' => Product::factory(),
            'quantity' => 1,
        ];
    }
}
