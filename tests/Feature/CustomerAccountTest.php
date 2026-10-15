<?php

use App\Enums\UserType;
use App\Models\Address;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use PragmaRX\Google2FA\Google2FA;

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
    'delivery address' => ['put', 'customer.account.delivery-address.update'],
    'remove delivery address' => ['delete', 'customer.account.delivery-address.destroy'],
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

test('customers can view the account page with their saved delivery address', function () {
    $user = User::factory()->create();
    $address = Address::factory()->create([
        'user_id' => $user->id,
        'receiver_name' => 'Emma Walker',
        'city' => 'Sydney',
    ]);

    $this->actingAs($user, 'customer');

    $this->get(route('customer.account.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('customer/account/index')
            ->has('passwordRules')
            ->has('canManageTwoFactor')
            ->has('twoFactorEnabled')
            ->has('requiresConfirmation')
            ->where('deliveryAddress.name', 'Emma Walker')
            ->where('deliveryAddress.mobile', $address->phone)
            ->where('deliveryAddress.city', 'Sydney')
            ->missing('billingAddress')
            ->missing('regions'));
});

test('the account page has no delivery address until one is saved', function () {
    $this->actingAs(User::factory()->create(), 'customer')
        ->get(route('customer.account.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('deliveryAddress', null));
});

test('customers can update their profile from the account page', function () {
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
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('customer.account.index'));

    $user->refresh();

    expect($user->name)->toBe('Account User')
        ->and($user->email)->toBe('account@example.com')
        ->and($user->phone)->toBe('+61412987654')
        ->and($user->phone_verified_at)->toBeNull();

    $this->assertDatabaseMissing('addresses', ['user_id' => $user->id]);
});

test('customers can remove their avatar from the account page', function () {
    Storage::fake('public');

    $user = User::factory()->create();

    $this->actingAs($user, 'customer')
        ->from(route('customer.account.index'))
        ->patch(route('customer.account.profile.update'), [
            'name' => $user->name,
            'phone' => $user->phone,
            'email' => $user->email,
            'avatar' => UploadedFile::fake()->image('avatar.png', 120, 120),
        ])
        ->assertSessionHasNoErrors();

    expect($user->refresh()->hasMedia('avatar'))->toBeTrue();

    $this->actingAs($user, 'customer')
        ->from(route('customer.account.index'))
        ->patch(route('customer.account.profile.update'), [
            'name' => $user->name,
            'phone' => $user->phone,
            'email' => $user->email,
            'remove_avatar' => '1',
        ])
        ->assertSessionHasNoErrors();

    expect($user->refresh()->hasMedia('avatar'))->toBeFalse();
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

    expect($user->hasMedia('avatar'))->toBeTrue()
        ->and($user->getFirstMedia('avatar')->mime_type)->toBe('image/webp');
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

test('customers can save their default delivery address', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'customer')
        ->from(route('customer.account.index'))
        ->put(route('customer.account.delivery-address.update'), [
            'name' => 'Emma Walker',
            'mobile' => '0412987654',
            'address' => '3 Boucaut Street',
            'city' => 'Sydney',
            'postal_code' => '2000',
            'latitude' => -33.8688,
            'longitude' => 151.2093,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('customer.account.index'));

    $this->assertDatabaseHas('addresses', [
        'user_id' => $user->id,
        'receiver_name' => 'Emma Walker',
        'phone' => '0412987654',
        'address' => '3 Boucaut Street',
        'city' => 'Sydney',
        'postal_code' => '2000',
    ]);
});

test('saving the delivery address again updates the single saved address', function () {
    $user = User::factory()->create();
    Address::factory()->create([
        'user_id' => $user->id,
        'address' => 'Old Street',
    ]);

    $this->actingAs($user, 'customer')
        ->from(route('customer.account.index'))
        ->put(route('customer.account.delivery-address.update'), [
            'name' => 'Emma Walker',
            'mobile' => '0412987654',
            'address' => 'New Street',
            'city' => 'Sydney',
            'latitude' => -33.8688,
            'longitude' => 151.2093,
        ])
        ->assertSessionHasNoErrors();

    expect(Address::query()->where('user_id', $user->id)->count())->toBe(1)
        ->and($user->savedAddress()->first()->address)->toBe('New Street');
});

test('delivery address save requires the contact fields and a map pin', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'customer')
        ->from(route('customer.account.index'))
        ->put(route('customer.account.delivery-address.update'), [])
        ->assertSessionHasErrors([
            'name',
            'mobile',
            'address',
            'city',
            'latitude',
            'longitude',
        ])
        ->assertRedirect(route('customer.account.index'));

    $this->assertDatabaseMissing('addresses', ['user_id' => $user->id]);
});

test('customers can remove their default delivery address', function () {
    $user = User::factory()->create();
    Address::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user, 'customer')
        ->from(route('customer.account.index'))
        ->delete(route('customer.account.delivery-address.destroy'))
        ->assertRedirect(route('customer.account.index'));

    $this->assertDatabaseMissing('addresses', ['user_id' => $user->id]);
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

test('guests cannot manage two factor authentication', function (string $method, string $routeName) {
    $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

    $this->{$method}(route($routeName), [])
        ->assertRedirect(route('login'));
})->with([
    'enable' => ['post', 'two-factor.enable'],
    'confirm' => ['post', 'two-factor.confirm'],
    'disable' => ['delete', 'two-factor.disable'],
]);

test('enabling two factor from the account page requires password confirmation', function () {
    $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    $user = User::factory()->create();

    $this->actingAs($user, 'customer')
        ->from(route('customer.account.index'))
        ->post(route('two-factor.enable'))
        ->assertRedirect(route('password.confirm'));
});

test('customers can enable confirm and disable two factor from the account page', function () {
    $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    $user = User::factory()->create();

    $this->actingAs($user, 'customer')
        ->withSession(['auth.password_confirmed_at' => time()])
        ->from(route('customer.account.index'))
        ->post(route('two-factor.enable'))
        ->assertRedirect(route('customer.account.index'));

    $user->refresh();

    expect($user->two_factor_secret)->not->toBeNull()
        ->and($user->two_factor_confirmed_at)->toBeNull();

    $code = (new Google2FA)->getCurrentOtp(
        Fortify::currentEncrypter()->decrypt($user->two_factor_secret),
    );

    $this->actingAs($user, 'customer')
        ->withSession(['auth.password_confirmed_at' => time()])
        ->from(route('customer.account.index'))
        ->post(route('two-factor.confirm'), ['code' => $code])
        ->assertRedirect(route('customer.account.index'));

    expect($user->refresh()->two_factor_confirmed_at)->not->toBeNull();

    $this->actingAs($user, 'customer')
        ->withSession(['auth.password_confirmed_at' => time()])
        ->from(route('customer.account.index'))
        ->delete(route('two-factor.disable'))
        ->assertRedirect(route('customer.account.index'));

    $user->refresh();

    expect($user->two_factor_secret)->toBeNull()
        ->and($user->two_factor_confirmed_at)->toBeNull();
});

test('customers cannot view recovery codes without password confirmation', function () {
    $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    $user = User::factory()->withTwoFactor()->create();

    $this->actingAs($user, 'customer')
        ->from(route('customer.account.index'))
        ->get(route('two-factor.recovery-codes'))
        ->assertRedirect(route('password.confirm'));
});

test('customers can view recovery codes after confirming their password', function () {
    $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    $user = User::factory()->withTwoFactor()->create();

    $this->actingAs($user, 'customer')
        ->withSession(['auth.password_confirmed_at' => time()])
        ->from(route('customer.account.index'))
        ->get(route('two-factor.recovery-codes'))
        ->assertOk()
        ->assertExactJson(['recovery-code-1']);
});
