<?php

namespace App\Services;

use App\Contracts\SendsSms;
use App\Enums\PhoneOtpPurpose;
use App\Models\PhoneOtp;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PhoneOtpService
{
    public const CODE_LENGTH = 6;

    public const TTL_MINUTES = 10;

    public const MAX_ATTEMPTS = 5;

    public function __construct(private SendsSms $sms) {}

    /**
     * Issue a new OTP for the given phone and purpose. Always sends an SMS
     * (callers decide whether the phone exists for enumeration safety).
     */
    public function issue(string $phoneE164, PhoneOtpPurpose $purpose): PhoneOtp
    {
        PhoneOtp::query()
            ->where('phone', $phoneE164)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->delete();

        $code = $this->generateCode();

        $otp = PhoneOtp::query()->create([
            'phone' => $phoneE164,
            'purpose' => $purpose,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(self::TTL_MINUTES),
            'attempts' => 0,
        ]);

        $this->sms->send(
            $phoneE164,
            __('Your verification code is :code. It expires in :minutes minutes.', [
                'code' => $code,
                'minutes' => self::TTL_MINUTES,
            ]),
        );

        return $otp;
    }

    /**
     * @throws ValidationException
     */
    public function verify(string $phoneE164, PhoneOtpPurpose $purpose, string $code): PhoneOtp
    {
        $otp = PhoneOtp::query()
            ->where('phone', $phoneE164)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->latest('id')
            ->first();

        if ($otp === null || $otp->isExpired()) {
            throw ValidationException::withMessages([
                'code' => __('This verification code is invalid or has expired.'),
            ]);
        }

        if ($otp->attempts >= self::MAX_ATTEMPTS) {
            throw ValidationException::withMessages([
                'code' => __('Too many attempts. Please request a new code.'),
            ]);
        }

        $otp->increment('attempts');

        if (! Hash::check($code, $otp->code_hash)) {
            throw ValidationException::withMessages([
                'code' => __('This verification code is invalid or has expired.'),
            ]);
        }

        $otp->forceFill(['consumed_at' => now()])->save();

        return $otp->refresh();
    }

    private function generateCode(): string
    {
        $max = (10 ** self::CODE_LENGTH) - 1;

        return str_pad((string) random_int(0, $max), self::CODE_LENGTH, '0', STR_PAD_LEFT);
    }
}
