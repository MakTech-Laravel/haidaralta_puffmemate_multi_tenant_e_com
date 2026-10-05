<?php

namespace App\Services\Customer;

use App\Enums\CustomerRegistrationStatus;
use App\Enums\OtpPurpose;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class CustomerClaimService
{
    public function __construct(private OtpService $otp) {}

    public function begin(User $customer, string $name, string $password): void
    {
        Cache::put($this->cacheKey($customer), [
            'name' => $name,
            'password' => $password,
        ], now()->addMinutes(OtpService::TTL_MINUTES));

        $this->otp->issue($customer->phone, OtpPurpose::AccountClaim, $customer);
    }

    /**
     * @throws ValidationException
     */
    public function complete(User $customer, string $code): User
    {
        $pending = Cache::get($this->cacheKey($customer));

        if (! is_array($pending) || ! isset($pending['name'], $pending['password'])) {
            throw ValidationException::withMessages([
                'code' => __('This verification code is invalid or has expired.'),
            ]);
        }

        $this->otp->verify($customer->phone, OtpPurpose::AccountClaim, $code, $customer);

        $customer->forceFill([
            'name' => $pending['name'],
            'password' => $pending['password'],
            'registration_status' => CustomerRegistrationStatus::Registered,
            'phone_verified_at' => now(),
        ])->save();

        Cache::forget($this->cacheKey($customer));

        return $customer->refresh();
    }

    private function cacheKey(User $customer): string
    {
        return 'customer-claim:'.$customer->id;
    }
}
