<?php

namespace App\Services;

use App\Contracts\SendsSms;
use App\Enums\OtpPurpose;
use App\Models\OtpRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class OtpService
{
    public const CODE_LENGTH = 6;

    public const TTL_MINUTES = 10;

    public const MAX_ATTEMPTS = 5;

    public function __construct(private SendsSms $sms) {}

    /**
     * Issue a new OTP for the given phone and purpose. Always sends an SMS
     * (callers decide whether the phone exists for enumeration safety).
     */
    public function issue(string $phoneE164, OtpPurpose $purpose, ?Model $subject = null, ?int $ttlMinutes = null): OtpRecord
    {
        return $this->issueCode($phoneE164, $purpose, $subject, $ttlMinutes)[0];
    }

    /**
     * Issue a code and send the SMS. The plaintext is returned once so other
     * channels (email, in-app) can carry the same code.
     *
     * @return array{0: OtpRecord, 1: string}
     */
    public function issueCode(string $phoneE164, OtpPurpose $purpose, ?Model $subject = null, ?int $ttlMinutes = null): array
    {
        $this->activeQuery($phoneE164, $purpose, $subject)->delete();

        $code = $this->generateCode();
        $minutes = $ttlMinutes ?? self::TTL_MINUTES;

        $otp = OtpRecord::query()->create([
            'phone' => $phoneE164,
            'purpose' => $purpose,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes($minutes),
            'attempts' => 0,
        ]);

        $this->sms->send(
            $phoneE164,
            __('Your verification code is :code. It expires in :minutes minutes.', [
                'code' => $code,
                'minutes' => $minutes,
            ]),
        );

        return [$otp, $code];
    }

    /**
     * @throws ValidationException
     */
    public function verify(string $phoneE164, OtpPurpose $purpose, string $code, ?Model $subject = null): OtpRecord
    {
        $otp = $this->activeQuery($phoneE164, $purpose, $subject)
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

    /**
     * @return Builder<OtpRecord>
     */
    private function activeQuery(string $phoneE164, OtpPurpose $purpose, ?Model $subject): Builder
    {
        $query = OtpRecord::query()
            ->where('phone', $phoneE164)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at');

        if ($subject === null) {
            return $query
                ->whereNull('subject_type')
                ->whereNull('subject_id');
        }

        return $query
            ->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', $subject->getKey());
    }

    private function generateCode(): string
    {
        $max = (10 ** self::CODE_LENGTH) - 1;

        return str_pad((string) random_int(0, $max), self::CODE_LENGTH, '0', STR_PAD_LEFT);
    }
}
