<?php

use App\Models\User;
use App\Services\Sms\FakeSmsSender;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::resetPasswords());
});

test('reset password link screen can be rendered', function () {
    $response = $this->get(route('password.request'));

    $response->assertOk();
});

test('password reset otp can be requested', function () {
    $user = User::factory()->create([
        'phone' => '+61412987654',
    ]);

    $response = $this->post(route('password.email'), ['phone' => '0412987654']);

    $response->assertRedirect(route('password.otp'))->assertSessionHas('status');

    /** @var FakeSmsSender $sms */
    $sms = app(FakeSmsSender::class);
    expect($sms->lastCode())->not->toBeNull();
});

test('password reset otp screen can be rendered after request', function () {
    User::factory()->create(['phone' => '+61412987654']);

    $this->post(route('password.email'), ['phone' => '+61412987654']);

    $this->get(route('password.otp'))->assertOk();
});

test('password can be reset after otp verification', function () {
    $user = User::factory()->create([
        'phone' => '+61412987654',
        'password' => Hash::make('old-password'),
    ]);

    $this->post(route('password.email'), ['phone' => '+61412987654']);

    /** @var FakeSmsSender $sms */
    $sms = app(FakeSmsSender::class);

    $this->post(route('password.otp.store'), [
        'phone' => '+61412987654',
        'code' => $sms->lastCode(),
    ])->assertRedirect(route('password.reset'));

    $this->get(route('password.reset'))->assertOk();

    $this->post(route('password.update'), [
        'phone' => '+61412987654',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('login'))
        ->assertSessionHas('status', __('passwords.reset'));

    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
});

test('password cannot be reset without a verified otp session', function () {
    $user = User::factory()->create(['phone' => '+61412987654']);

    $response = $this->post(route('password.update'), [
        'phone' => $user->phone,
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);

    $response->assertSessionHasErrors('phone');
});
