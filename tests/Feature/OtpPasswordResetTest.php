<?php

use App\Enums\OtpPurpose;
use App\Models\User;
use App\Services\Sms\FakeSmsSender;
use Illuminate\Support\Facades\Hash;

test('forgot password always returns success without revealing phone existence', function () {
    $response = $this->from(route('password.request'))
        ->post(route('password.email'), [
            'phone' => '0412987654',
        ]);

    $response->assertRedirect(route('password.otp'));
    $response->assertSessionHas('status');
});

test('forgot password sends otp for an existing user and allows password reset', function () {
    $user = User::factory()->create([
        'phone' => '+61412987654',
        'password' => 'old-password',
    ]);

    $this->post(route('password.email'), [
        'phone' => '0412987654',
    ])->assertRedirect(route('password.otp'));

    /** @var FakeSmsSender $sms */
    $sms = app(FakeSmsSender::class);
    expect($sms->lastCode())->not->toBeNull();

    $this->post(route('password.otp.store'), [
        'phone' => '+61412987654',
        'code' => $sms->lastCode(),
    ])->assertRedirect(route('password.reset'));

    $this->post(route('password.update'), [
        'phone' => '+61412987654',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])->assertRedirect(route('login'));

    expect(Hash::check('new-password', $user->fresh()->password))->toBeTrue();
});

test('password reset otp purpose is stored', function () {
    User::factory()->create(['phone' => '+61412987654']);

    $this->post(route('password.email'), [
        'phone' => '+61412987654',
    ]);

    $this->assertDatabaseHas('otp_records', [
        'phone' => '+61412987654',
        'purpose' => OtpPurpose::PasswordReset->value,
        'subject_type' => null,
        'subject_id' => null,
    ]);
});
