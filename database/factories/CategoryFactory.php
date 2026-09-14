<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'parent_id' => null,
            'name' => Str::title($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('###'),
            'image' => null,
            'slogan' => null,
            'color_code' => null,
            'status' => true,
            'featured' => false,
            'show_in_nav' => false,
            'nav_sort' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['status' => false]);
    }

    public function featured(): static
    {
        return $this->state(fn (): array => ['featured' => true]);
    }

    public function inNav(?int $sort = null): static
    {
        return $this->state(fn (): array => [
            'show_in_nav' => true,
            'nav_sort' => $sort,
        ]);
    }
}
