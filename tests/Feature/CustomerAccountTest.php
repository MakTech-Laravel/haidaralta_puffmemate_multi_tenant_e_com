<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected from customer account', function () {
    $this->get(route('customer.account.index'))
        ->assertRedirect(route('login'));
});

test('customers can view the account page', function () {
    $user = User::factory()->create();
    $this->actingAs($user, 'customer');

    $this->get(route('customer.account.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('customer/account/index')
            ->has('passwordRules')
            ->has('canManageTwoFactor')
            ->has('twoFactorEnabled'));
});

test('customers can update profile from the account page', function () {
    $user = User::factory()->create([
        'phone' => '+61412345678',
        'phone_verified_at' => now(),
    ]);

    $this->actingAs($user, 'customer')
        ->from(route('customer.account.index'))
        ->patch(route('profile.update'), [
            'name' => 'Account User',
            'phone' => '0412987654',
            'email' => 'account@example.com',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('customer.account.index'));

    $user->refresh();

    expect($user->name)->toBe('Account User')
        ->and($user->email)->toBe('account@example.com');
});
