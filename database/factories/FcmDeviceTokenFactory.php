<?php

namespace Database\Factories;

use App\Models\FcmDeviceToken;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FcmDeviceToken>
 */
class FcmDeviceTokenFactory extends Factory
{
    protected $model = FcmDeviceToken::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'token' => 'fcm-test-'.fake()->unique()->sha256(),
            'device_name' => fake()->optional()->userAgent(),
            'user_agent' => fake()->userAgent(),
            'platform' => 'web',
            'last_used_at' => now(),
        ];
    }
}
