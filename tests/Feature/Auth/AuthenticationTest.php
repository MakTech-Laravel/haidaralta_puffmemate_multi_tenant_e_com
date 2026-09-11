<?php

use App\Models\User;
use Laravel\Fortify\Features;

test('login screen can be rendered', function () {
    $response = $this->get(route('login'));

    $response->assertOk();
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post(route('login.store'), [
        'phone' => $user->phone,
        'password' => 'password',
    ]);

    $this->assertAuthenticated('customer');
    $response->assertRedirect('/dashboard');
});

test('users with two factor enabled are not redirected to fortify two factor challenge', function () {
    $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

    $user = User::factory()->withTwoFactor()->create();

    $response = $this->post(route('login.store'), [
        'phone' => $user->phone,
        'password' => 'password',
    ]);

    $response->assertRedirect('/dashboard');
    $this->assertAuthenticated('customer');
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'phone' => $user->phone,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest('platform');
    $this->assertGuest('branch');
    $this->assertGuest('driver');
    $this->assertGuest('customer');
});
