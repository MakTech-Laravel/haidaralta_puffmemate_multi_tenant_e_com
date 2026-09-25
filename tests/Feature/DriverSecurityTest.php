<?php

use App\Enums\UserType;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use PragmaRX\Google2FA\Google2FA;

test('guests cannot open driver security', function () {
    $this->get(route('driver.security.index'))
        ->assertRedirect(route('login'));
});

test('guests cannot update the driver password', function () {
    $this->put(route('driver.security.password.update'), [])
        ->assertRedirect(route('login'));
});

test('customers cannot update the driver password', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'customer')
        ->put(route('driver.security.password.update'), [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
        ->assertRedirect(route('login'));
});

test('drivers see security status and two factor props', function () {
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'phone' => '+61400000111',
        'phone_verified_at' => now(),
    ]);

    $driver->forceFill([
        'two_factor_secret' => 'secret',
        'two_factor_confirmed_at' => now(),
    ])->save();

    $driver->passkeys()->create([
        'name' => 'Phone',
        'credential_id' => 'driver-security-credential',
        'credential' => ['id' => 'driver-security-credential'],
    ]);

    $this->actingAs($driver, 'driver')
        ->get(route('driver.security.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('driver/security/index')
            ->where('phone', '+61400000111')
            ->where('phoneVerified', true)
            ->where('canManageTwoFactor', true)
            ->where('twoFactorEnabled', true)
            ->where('requiresConfirmation', true)
            ->has('passwordRules')
            ->where('passkeyCount', 1)
            ->missing('password'));
});

test('drivers can update their password from the security page', function () {
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
    ]);

    $this->actingAs($driver, 'driver')
        ->from(route('driver.security.index'))
        ->put(route('driver.security.password.update'), [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('driver.security.index'));

    expect(Hash::check('new-password', $driver->refresh()->password))->toBeTrue();
});

test('the current password must be correct to change the driver password', function () {
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
    ]);

    $this->actingAs($driver, 'driver')
        ->from(route('driver.security.index'))
        ->put(route('driver.security.password.update'), [
            'current_password' => 'wrong-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
        ->assertSessionHasErrors('current_password')
        ->assertRedirect(route('driver.security.index'));
});

test('enabling two factor from the security page requires password confirmation', function () {
    $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
    ]);

    $this->actingAs($driver, 'driver')
        ->from(route('driver.security.index'))
        ->post(route('two-factor.enable'))
        ->assertRedirect(route('password.confirm'));
});

test('drivers can enable confirm and disable two factor from the security page', function () {
    $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
    ]);

    $this->actingAs($driver, 'driver')
        ->withSession(['auth.password_confirmed_at' => time()])
        ->from(route('driver.security.index'))
        ->post(route('two-factor.enable'))
        ->assertRedirect(route('driver.security.index'));

    $driver->refresh();

    expect($driver->two_factor_secret)->not->toBeNull()
        ->and($driver->two_factor_confirmed_at)->toBeNull();

    $code = (new Google2FA)->getCurrentOtp(
        Fortify::currentEncrypter()->decrypt($driver->two_factor_secret),
    );

    $this->actingAs($driver, 'driver')
        ->withSession(['auth.password_confirmed_at' => time()])
        ->from(route('driver.security.index'))
        ->post(route('two-factor.confirm'), ['code' => $code])
        ->assertRedirect(route('driver.security.index'));

    expect($driver->refresh()->two_factor_confirmed_at)->not->toBeNull();

    $this->actingAs($driver, 'driver')
        ->withSession(['auth.password_confirmed_at' => time()])
        ->from(route('driver.security.index'))
        ->delete(route('two-factor.disable'))
        ->assertRedirect(route('driver.security.index'));

    $driver->refresh();

    expect($driver->two_factor_secret)->toBeNull()
        ->and($driver->two_factor_confirmed_at)->toBeNull();
});

test('drivers cannot view recovery codes without password confirmation', function () {
    $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    $driver = User::factory()->withTwoFactor()->create([
        'user_type' => UserType::DRIVER,
    ]);

    $this->actingAs($driver, 'driver')
        ->from(route('driver.security.index'))
        ->get(route('two-factor.recovery-codes'))
        ->assertRedirect(route('password.confirm'));
});

test('drivers can view recovery codes after confirming their password', function () {
    $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    $driver = User::factory()->withTwoFactor()->create([
        'user_type' => UserType::DRIVER,
    ]);

    $this->actingAs($driver, 'driver')
        ->withSession(['auth.password_confirmed_at' => time()])
        ->from(route('driver.security.index'))
        ->get(route('two-factor.recovery-codes'))
        ->assertOk()
        ->assertExactJson(['recovery-code-1']);
});
