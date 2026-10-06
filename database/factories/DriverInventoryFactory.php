<?php

namespace Database\Factories;

use App\Enums\UserType;
use App\Models\Branch;
use App\Models\DriverInventory;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DriverInventory>
 */
class DriverInventoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state([
                'user_type' => UserType::DRIVER,
                'branch_id' => null,
            ]),
            'branch_id' => Branch::factory(),
            'product_id' => Product::factory(),
            'variant_id' => null,
            'quantity' => fake()->numberBetween(0, 50),
        ];
    }
}
