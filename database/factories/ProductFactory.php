<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'name' => Str::title($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('###'),
            'sku' => strtoupper(fake()->unique()->bothify('SKU-####??')),
            'description' => fake()->optional()->paragraph(),
            'image' => null,
            'status' => true,
            'created_by' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Product $product): void {
            if ($product->categories()->exists()) {
                return;
            }

            $product->categories()->attach(Category::factory()->create());
        });
    }

    public function createdBy(User $user): static
    {
        return $this->state(fn (): array => ['created_by' => $user->id]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['status' => false]);
    }
}
