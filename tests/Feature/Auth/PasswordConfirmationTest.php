<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('confirm password screen can be rendered', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'customer')->get(route('password.confirm'));

    $response->assertOk();

    $response->assertInertia(fn (Assert $page) => $page
        ->component('settings/confirm-password'),
    );
});

test('password confirmation requires authentication', function () {
    $response = $this->get(route('password.confirm'));

    $response->assertRedirect(route('login'));
});

test('password can be confirmed and returns to the intended security page', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'customer')
        ->get(route('security.edit'))
        ->assertRedirect(route('password.confirm'));

    $this->actingAs($user, 'customer')
        ->followingRedirects()
        ->get(route('security.edit'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/confirm-password'),
        );

    $this->actingAs($user, 'customer')
        ->from(route('password.confirm'))
        ->post(route('password.confirm.store'), [
            'password' => 'password',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('security.edit'));
});
