<?php

use App\Enums\OtpPurpose;
use App\Models\OtpRecord;
use App\Models\User;
use App\Services\OtpService;
use App\Services\Sms\FakeSmsSender;

test('otp records with a subject do not collide with phone-only otps', function () {
    $user = User::factory()->create([
        'phone' => '+61412345678',
    ]);

    /** @var OtpService $otpService */
    $otpService = app(OtpService::class);

    /** @var FakeSmsSender $sms */
    $sms = app(FakeSmsSender::class);

    $otpService->issue($user->phone, OtpPurpose::PhoneVerify);
    $phoneOnlyCode = $sms->lastCode();

    $otpService->issue($user->phone, OtpPurpose::PhoneVerify, $user);
    $subjectCode = $sms->lastCode();

    expect($phoneOnlyCode)->not->toBe($subjectCode)
        ->and(OtpRecord::query()->whereNull('consumed_at')->count())->toBe(2);

    $otpService->verify($user->phone, OtpPurpose::PhoneVerify, $phoneOnlyCode);

    expect(
        OtpRecord::query()
            ->whereNull('subject_type')
            ->whereNull('subject_id')
            ->whereNotNull('consumed_at')
            ->exists()
    )->toBeTrue()
        ->and(
            OtpRecord::query()
                ->where('subject_type', $user->getMorphClass())
                ->where('subject_id', $user->id)
                ->whereNull('consumed_at')
                ->exists()
        )->toBeTrue();

    $otpService->verify($user->phone, OtpPurpose::PhoneVerify, $subjectCode, $user);

    expect(
        OtpRecord::query()
            ->where('subject_type', $user->getMorphClass())
            ->where('subject_id', $user->id)
            ->whereNotNull('consumed_at')
            ->exists()
    )->toBeTrue();
});

test('reissuing an otp replaces only the matching subject scope', function () {
    $user = User::factory()->create([
        'phone' => '+61412345678',
    ]);

    /** @var OtpService $otpService */
    $otpService = app(OtpService::class);

    $otpService->issue($user->phone, OtpPurpose::PasswordReset);
    $otpService->issue($user->phone, OtpPurpose::PasswordReset, $user);
    $otpService->issue($user->phone, OtpPurpose::PasswordReset, $user);

    expect(
        OtpRecord::query()
            ->where('purpose', OtpPurpose::PasswordReset)
            ->whereNull('consumed_at')
            ->count()
    )->toBe(2)
        ->and(
            OtpRecord::query()
                ->whereNull('subject_type')
                ->whereNull('consumed_at')
                ->count()
        )->toBe(1)
        ->and(
            OtpRecord::query()
                ->where('subject_id', $user->id)
                ->whereNull('consumed_at')
                ->count()
        )->toBe(1);
});
