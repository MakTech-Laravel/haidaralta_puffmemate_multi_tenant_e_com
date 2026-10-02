<?php

namespace Database\Factories;

use App\Enums\ContactInquiryStatus;
use App\Enums\ContactInquiryType;
use App\Models\ContactInquiry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContactInquiry>
 */
class ContactInquiryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'full_name' => fake()->name(),
            'phone' => fake()->numerify('04########'),
            'email' => fake()->optional()->safeEmail(),
            'type' => fake()->randomElement(ContactInquiryType::cases()),
            'message' => fake()->paragraph(),
            'status' => ContactInquiryStatus::New,
            'read_at' => null,
            'ip_address' => fake()->optional()->ipv4(),
            'user_agent' => fake()->optional()->userAgent(),
        ];
    }

    public function read(): static
    {
        return $this->state(fn (): array => [
            'status' => ContactInquiryStatus::Read,
            'read_at' => now(),
        ]);
    }
}
