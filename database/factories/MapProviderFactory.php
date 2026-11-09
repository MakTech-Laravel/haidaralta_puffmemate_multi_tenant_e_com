<?php

namespace Database\Factories;

use App\Models\MapProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MapProvider>
 */
class MapProviderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => 'google',
            'is_enabled' => true,
            'public_key' => null,
            'server_key' => null,
            'options' => null,
            'daily_limit' => null,
            'monthly_limit' => null,
        ];
    }

    public function withKeys(string $publicKey = 'public-test-key', string $serverKey = 'server-test-key'): static
    {
        return $this->state(fn (): array => [
            'public_key' => $publicKey,
            'server_key' => $serverKey,
        ]);
    }
}
