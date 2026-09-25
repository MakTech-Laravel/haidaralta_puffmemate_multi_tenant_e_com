<?php

namespace Database\Factories;

use App\Enums\CouponType;
use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Coupon>
 */
class CouponFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('SAVE##')),
            'type' => CouponType::Fixed,
            'value' => 10,
            'min_subtotal' => null,
            'max_discount' => null,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'usage_limit' => null,
            'usage_limit_per_customer' => null,
            'status' => true,
        ];
    }

    public function percent(): static
    {
        return $this->state(fn (): array => [
            'type' => CouponType::Percent,
            'value' => 15,
        ]);
    }
}
