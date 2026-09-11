<?php

namespace Database\Factories;

use App\Enums\UserType;
use App\Models\User;
use App\Support\Phone;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Monotonic suffix so factory phones stay unique and valid AU mobiles.
     */
    protected static int $phoneSequence = 0;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'phone' => $this->uniqueValidAuMobile(),
            'phone_verified_at' => now(),
            'email' => fake()->boolean(70) ? fake()->unique()->safeEmail() : null,
            'email_verified_at' => null,
            'password' => static::$password ??= 'password',
            'user_type' => UserType::CUSTOMER,
            'branch_id' => null,
            'is_active' => true,
            'remember_token' => Str::random(10),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ];
    }

    /**
     * Indicate that the model's phone is unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'phone_verified_at' => null,
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the model has two-factor authentication configured.
     */
    public function withTwoFactor(): static
    {
        return $this->state(fn (array $attributes) => [
            'two_factor_secret' => encrypt('secret'),
            'two_factor_recovery_codes' => encrypt(json_encode(['recovery-code-1'])),
            'two_factor_confirmed_at' => now(),
        ]);
    }

    private function uniqueValidAuMobile(): string
    {
        static::$phoneSequence++;

        // 0410 000 001 … stays within AU mobile ranges accepted by libphonenumber.
        $local = sprintf('0410%06d', static::$phoneSequence);

        return Phone::toE164($local);
    }
}
