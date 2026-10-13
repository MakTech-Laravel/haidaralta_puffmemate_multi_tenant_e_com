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
            'email' => fake()->optional()->safeEmail(),
            'address' => fake()->streetAddress().', '.fake()->city(),
            'latitude' => (string) fake()->latitude(-44, -10),
            'longitude' => (string) fake()->longitude(113, 154),
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

    public function rejected(string $reason = 'Not able to supply this request.'): static
    {
        return $this->state(fn (): array => [
            'status' => WholesaleInquiryStatus::Rejected,
            'read_at' => now(),
            'rejection_reason' => $reason,
            'reviewed_at' => now(),
        ]);
    }
}
