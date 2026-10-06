<?php

namespace Database\Factories;

use App\Enums\PackOfferType;
use App\Models\Branch;
use App\Models\Pack;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Pack>
 */
class PackFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'branch_id' => Branch::factory(),
            'name' => Str::title($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('###'),
            'description' => fake()->optional()->sentence(),
            'offer_type' => PackOfferType::Percent,
            'offer_value' => '20.00',
            'subtotal' => '100.00',
            'pack_price' => '80.00',
            'quantity' => 5,
            'status' => true,
            'starts_at' => null,
            'ends_at' => null,
        ];
    }
}
