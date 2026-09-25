<?php

use App\Enums\AustralianState;
use App\Enums\CustomerAddressType;
use App\Enums\UserType;
use App\Models\CustomerAddress;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected from customer account', function () {
    $this->get(route('customer.account.index'))
        ->assertRedirect(route('login'));
});

test('guests are redirected from customer account mutations', function (string $method, string $routeName) {
    $this->{$method}(route($routeName), [])
        ->assertRedirect(route('login'));
})->with([
    'profile' => ['patch', 'customer.account.profile.update'],
    'password' => ['put', 'customer.account.password.update'],
    'billing' => ['patch', 'customer.account.billing.update'],
]);

test('platform sessions cannot update the customer account', function () {
    $user = User::factory()->create([
        'user_type' => UserType::PLATFORM,
    ]);

    $this->actingAs($user, 'platform')
        ->patch(route('customer.account.profile.update'), [
            'name' => 'Hacker',
            'phone' => '+61412345678',
        ])
        ->assertRedirect(route('login'));
});

test('customers can view the account page', function () {
    $user = User::factory()->create();
    $shipping = CustomerAddress::factory()->shipping()->create([
        'user_id' => $user->id,
        'region' => AustralianState::NewSouthWales,
        'city' => 'Sydney',
        'zip' => '2000',
        'address' => '1 George Street',
    ]);

    $this->actingAs($user, 'customer');

    $this->get(route('customer.account.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('customer/account/index')
            ->has('passwordRules')
            ->has('canManageTwoFactor')
            ->has('twoFactorEnabled')
            ->has('regions')
            ->where('shippingAddress.city', $shipping->city)
            ->where('shippingAddress.region', 'nsw')
            ->where('billingAddress', null));
});

test('customers can update profile and shipping address from the account page', function () {
    $user = User::factory()->create([
        'phone' => '+61412345678',
        'phone_verified_at' => now(),
    ]);

    $this->actingAs($user, 'customer')
        ->from(route('customer.account.index'))
        ->patch(route('customer.account.profile.update'), [
            'name' => 'Account User',
            'phone' => '0412987654',
            'email' => 'account@example.com',
            'region' => 'nsw',
            'city' => 'Surry Hills',
            'zip' => '2010',
            'address' => '418 Crown Street',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('customer.account.index'));

    $user->refresh();

    expect($user->name)->toBe('Account User')
        ->and($user->email)->toBe('account@example.com')
        ->and($user->phone)->toBe('+61412987654')
        ->and($user->phone_verified_at)->toBeNull();

    $this->assertDatabaseHas('customer_addresses', [
        'user_id' => $user->id,
        'type' => CustomerAddressType::Shipping->value,
        'region' => 'nsw',
        'city' => 'Surry Hills',
        'zip' => '2010',
        'address' => '418 Crown Street',
    ]);
});

test('phone verification is unchanged when the account phone stays the same', function () {
    $user = User::factory()->create([
        'phone' => '+61412345678',
        'phone_verified_at' => now(),
        'email' => 'keep@example.com',
        'email_verified_at' => now(),
    ]);

    $this->actingAs($user, 'customer')
        ->from(route('customer.account.index'))
        ->patch(route('customer.account.profile.update'), [
            'name' => 'Same Phone',
            'phone' => $user->phone,
            'email' => $user->email,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('customer.account.index'));

    expect($user->refresh()->phone_verified_at)->not->toBeNull();
});

test('customers can upload an avatar from the account page', function () {
    Storage::fake('public');

    $user = User::factory()->create([
        'phone' => '+61412345678',
        'phone_verified_at' => now(),
        'avatar' => null,
    ]);

    $avatar = UploadedFile::fake()->image('avatar.png', 120, 120);

    $this->actingAs($user, 'customer')
        ->from(route('customer.account.index'))
        ->patch(route('customer.account.profile.update'), [
            'name' => $user->name,
            'phone' => $user->phone,
            'email' => $user->email,
            'avatar' => $avatar,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('customer.account.index'));

    $user->refresh();

    expect($user->avatar)->not->toBeNull();
    Storage::disk('public')->assertExists($user->avatar);
});

test('customer profile update rejects a phone that belongs to another user', function () {
    $other = User::factory()->create();
    $user = User::factory()->create([
        'phone' => '+61412345678',
        'phone_verified_at' => now(),
    ]);

    $this->actingAs($user, 'customer')
        ->from(route('customer.account.index'))
        ->patch(route('customer.account.profile.update'), [
            'name' => $user->name,
            'phone' => $other->phone,
            'email' => $user->email,
        ])
        ->assertSessionHasErrors('phone')
        ->assertRedirect(route('customer.account.index'));
});

test('customer profile update rejects an email that belongs to another user', function () {
    $other = User::factory()->create([
        'email' => 'taken@example.com',
    ]);
    $user = User::factory()->create([
        'phone' => '+61412345678',
        'phone_verified_at' => now(),
        'email' => 'mine@example.com',
    ]);

    $this->actingAs($user, 'customer')
        ->from(route('customer.account.index'))
        ->patch(route('customer.account.profile.update'), [
            'name' => $user->name,
            'phone' => $user->phone,
            'email' => $other->email,
        ])
        ->assertSessionHasErrors('email')
        ->assertRedirect(route('customer.account.index'));
});

test('customer profile update rejects an invalid region', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'customer')
        ->from(route('customer.account.index'))
        ->patch(route('customer.account.profile.update'), [
            'name' => $user->name,
            'phone' => $user->phone,
            'email' => $user->email,
            'region' => 'xx',
        ])
        ->assertSessionHasErrors('region')
        ->assertRedirect(route('customer.account.index'));
});

test('customers can save a billing address', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'customer')
        ->from(route('customer.account.index'))
        ->patch(route('customer.account.billing.update'), [
            'name' => 'Emma Walker',
            'address' => '3 Boucaut Street',
            'region' => 'nsw',
            'city' => 'Sydney',
            'zip' => '2000',
            'email' => 'billing@example.com',
            'phone' => '0412987654',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('customer.account.index'));

    $this->assertDatabaseHas('customer_addresses', [
        'user_id' => $user->id,
        'type' => CustomerAddressType::Billing->value,
        'name' => 'Emma Walker',
        'address' => '3 Boucaut Street',
        'region' => 'nsw',
        'city' => 'Sydney',
        'zip' => '2000',
        'email' => 'billing@example.com',
        'phone' => '+61412987654',
    ]);
});

test('billing address save requires the contact and street fields', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'customer')
        ->from(route('customer.account.index'))
        ->patch(route('customer.account.billing.update'), [])
        ->assertSessionHasErrors([
            'name',
            'address',
            'region',
            'city',
            'zip',
            'phone',
        ])
        ->assertRedirect(route('customer.account.index'));
});

test('customers can update their password from the account page', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'customer')
        ->from(route('customer.account.index'))
        ->put(route('customer.account.password.update'), [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('customer.account.index'));

    expect(Hash::check('new-password', $user->refresh()->password))->toBeTrue();
});

test('the current password must be correct to change the account password', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'customer')
        ->from(route('customer.account.index'))
        ->put(route('customer.account.password.update'), [
            'current_password' => 'wrong-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
        ->assertSessionHasErrors('current_password')
        ->assertRedirect(route('customer.account.index'));
});
