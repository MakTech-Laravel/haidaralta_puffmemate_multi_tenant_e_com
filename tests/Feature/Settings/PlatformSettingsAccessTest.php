<?php

use App\Enums\UserType;
use App\Models\User;

test('platform users can view the profile settings page', function () {
    $user = User::factory()->create([
        'user_type' => UserType::PLATFORM,
    ]);

    $this->actingAs($user, 'platform')
        ->get(route('profile.edit'))
        ->assertOk();
});

test('platform users can view the appearance settings page', function () {
    $user = User::factory()->create([
        'user_type' => UserType::PLATFORM,
    ]);

    $this->actingAs($user, 'platform')
        ->get(route('appearance.edit'))
        ->assertOk();
});

test('guests are redirected from profile settings to login', function () {
    $this->get(route('profile.edit'))
        ->assertRedirect(route('login'));
});
