<?php

use App\Enums\OtpPurpose;
use App\Models\User;
use App\Services\Sms\FakeSmsSender;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

test('registration requires phone and redirects to phone verification', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'phone' => '0412345678',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated('customer');
    $response->assertRedirect(route('phone.verification.notice'));

    $this->assertDatabaseHas('users', [
        'phone' => '+61412345678',
        'name' => 'Test User',
        'email' => null,
        'phone_verified_at' => null,
    ]);
});

test('duplicate phone registration is rejected', function () {
    User::factory()->create(['phone' => '+61412345678']);

    $this->post(route('register.store'), [
        'name' => 'Other User',
        'phone' => '0412345678',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors('phone');
});

test('phone verification marks phone_verified_at', function () {
    $user = User::factory()->unverified()->create([
        'phone' => '+61412345678',
    ]);

    $this->actingAs($user, 'customer')
        ->post(route('phone.verification.send'))
        ->assertRedirect();

    /** @var FakeSmsSender $sms */
    $sms = app(FakeSmsSender::class);
    expect($sms->lastCode())->not->toBeNull();

    $this->actingAs($user, 'customer')
        ->post(route('phone.verification.verify'), [
            'code' => $sms->lastCode(),
        ])
        ->assertRedirect();

    expect($user->fresh()->phone_verified_at)->not->toBeNull();

    $this->assertDatabaseHas('otp_records', [
        'phone' => '+61412345678',
        'purpose' => OtpPurpose::PhoneVerify->value,
        'subject_type' => null,
        'subject_id' => null,
    ]);
});
