<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('profile page includes avatar fields', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'customer')
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('settings/profile')
            ->has('profile.avatar')
            ->has('profile.avatar_url')
            ->where('profile.name', $user->name)
            ->where('profile.phone', $user->phone)
        );
});

test('profile avatar can be uploaded', function () {
    Storage::fake('public');

    $user = User::factory()->create([
        'phone' => '+61412345678',
        'phone_verified_at' => now(),
        'avatar' => null,
    ]);

    $avatar = UploadedFile::fake()->image('avatar.png', 120, 120);

    $this->actingAs($user, 'customer')
        ->from(route('profile.edit'))
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'phone' => $user->phone,
            'email' => $user->email,
            'avatar' => $avatar,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $user->refresh();

    expect($user->avatar)->not->toBeNull();
    Storage::disk('public')->assertExists($user->avatar);
});

test('profile avatar can be removed', function () {
    Storage::fake('public');

    $path = UploadedFile::fake()->image('old.png')->store('users/avatars', 'public');
    $user = User::factory()->create([
        'phone' => '+61412345678',
        'phone_verified_at' => now(),
        'avatar' => $path,
    ]);

    $this->actingAs($user, 'customer')
        ->from(route('profile.edit'))
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'phone' => $user->phone,
            'email' => $user->email,
            'remove_avatar' => true,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    expect($user->refresh()->avatar)->toBeNull();
    Storage::disk('public')->assertMissing($path);
});

test('profile information can be updated', function () {
    $user = User::factory()->create([
        'phone' => '+61412345678',
        'phone_verified_at' => now(),
    ]);

    $response = $this
        ->actingAs($user, 'customer')
        ->from(route('profile.edit'))
        ->patch(route('profile.update'), [
            'name' => 'Test User',
            'phone' => '0412987654',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $user->refresh();

    expect($user->name)->toBe('Test User')
        ->and($user->phone)->toBe('+61412987654')
        ->and($user->email)->toBe('test@example.com')
        ->and($user->phone_verified_at)->toBeNull();
});

test('phone verification status is unchanged when the phone number is unchanged', function () {
    $user = User::factory()->create([
        'phone' => '+61412345678',
        'phone_verified_at' => now(),
        'email' => 'keep@example.com',
        'email_verified_at' => now(),
    ]);

    $response = $this
        ->actingAs($user, 'customer')
        ->from(route('profile.edit'))
        ->patch(route('profile.update'), [
            'name' => 'Test User',
            'phone' => $user->phone,
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    expect($user->refresh()->phone_verified_at)->not->toBeNull();
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user, 'customer')
        ->delete(route('profile.destroy'), [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('home'));

    $this->assertGuest('customer');
    expect($user->fresh())->toBeNull();
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user, 'customer')
        ->from(route('profile.edit'))
        ->delete(route('profile.destroy'), [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrors('password')
        ->assertRedirect(route('profile.edit'));

    expect($user->fresh())->not->toBeNull();
});
