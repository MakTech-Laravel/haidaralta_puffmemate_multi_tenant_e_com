<?php

namespace Database\Factories;

use App\Models\DeliveryMethod;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<DeliveryMethod>
 */
class DeliveryMethodFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $label = fake()->unique()->words(2, true);

        return [
            'key' => Str::slug($label),
            'label' => Str::title($label),
            'description' => 'Test delivery method',
            'fee' => 0,
            'requires_slot' => false,
            'status' => true,
            'sort' => 0,
        ];
    }
}
