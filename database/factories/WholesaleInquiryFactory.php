<?php

namespace Database\Factories;

use App\Enums\WholesaleInquiryStatus;
use App\Models\WholesaleInquiry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WholesaleInquiry>
 */
class WholesaleInquiryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'full_name' => fake()->name(),
            'phone' => fake()->numerify('04########'),
            'company' => fake()->optional()->company(),
            'ask_question' => fake()->optional()->sentence(4),
            'email' => fake()->optional()->safeEmail(),
            'address' => fake()->streetAddress().', '.fake()->city(),
            'message' => fake()->paragraph(),
            'status' => WholesaleInquiryStatus::New,
            'read_at' => null,
            'ip_address' => fake()->optional()->ipv4(),
            'user_agent' => fake()->optional()->userAgent(),
        ];
    }

    public function read(): static
    {
        return $this->state(fn (): array => [
            'status' => WholesaleInquiryStatus::Read,
            'read_at' => now(),
        ]);
    }
}
