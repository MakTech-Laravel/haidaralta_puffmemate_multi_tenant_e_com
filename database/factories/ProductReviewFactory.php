<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductReview>
 */
class ProductReviewFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'order_id' => Order::factory()->delivered(),
            'product_id' => Product::factory(),
            'rating' => fake()->numberBetween(1, 5),
            'body' => 'Consistent pack quality and fast local delivery.',
            'hidden_at' => null,
        ];
    }

    public function hidden(): static
    {
        return $this->state(fn (): array => [
            'hidden_at' => now(),
        ]);
    }
}
