<?php

use App\Enums\UserType;
use App\Models\User;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'phone' => '0412345678',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated('customer');
    $response->assertRedirect(route('phone.verification.notice', absolute: false));

    $user = User::query()->where('phone', '+61412345678')->firstOrFail();

    expect($user->user_type)->toBe(UserType::CUSTOMER)
        ->and($user->branch_id)->toBeNull()
        ->and($user->name)->toBe('Test User')
        ->and($user->email)->toBeNull();
});

test('registration always creates a customer and ignores portal spoofing', function (string $spoofedType) {
    $this->post(route('register.store'), [
        'name' => 'Spoof Attempt',
        'phone' => '0412987654',
        'password' => 'password',
        'password_confirmation' => 'password',
        'user_type' => $spoofedType,
        'branch_id' => 1,
    ])->assertRedirect(route('phone.verification.notice', absolute: false));

    $user = User::query()->where('phone', '+61412987654')->firstOrFail();

    expect($user->user_type)->toBe(UserType::CUSTOMER)
        ->and($user->branch_id)->toBeNull()
        ->and($user->name)->toBe('Spoof Attempt')
        ->and($user->user_type)->not->toBe(UserType::PLATFORM)
        ->and($user->user_type)->not->toBe(UserType::BRANCH)
        ->and($user->user_type)->not->toBe(UserType::DRIVER);

    $this->assertAuthenticated('customer');
    $this->assertGuest('platform');
    $this->assertGuest('branch');
    $this->assertGuest('driver');
})->with([
    'platform' => [UserType::PLATFORM->value],
    'branch' => [UserType::BRANCH->value],
    'driver' => [UserType::DRIVER->value],
]);
