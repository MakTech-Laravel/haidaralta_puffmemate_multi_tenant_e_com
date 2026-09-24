<?php

namespace Database\Factories;

use App\Models\PriceRange;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PriceRange>
 */
class PriceRangeFactory extends Factory
{
    protected $model = PriceRange::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $label = fake()->unique()->words(2, true);
        $min = fake()->optional()->randomFloat(2, 0, 100);
        $max = $min === null
            ? fake()->optional()->randomFloat(2, 20, 500)
            : fake()->optional()->randomFloat(2, $min, $min + 200);

        return [
            'label' => Str::title($label),
            'slug' => Str::slug($label).'-'.fake()->unique()->numerify('###'),
            'min_amount' => $min,
            'max_amount' => $max,
            'sort_order' => fake()->numberBetween(0, 20),
            'status' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['status' => false]);
    }
}
