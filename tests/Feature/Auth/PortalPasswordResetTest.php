<?php

/**
 * Authoritative multi-guard password reset feature tests (Phases 4–7).
 * Starter-kit smoke coverage remains in PasswordResetTest.php.
 */

use App\Models\User;
use App\Services\Sms\FakeSmsSender;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::resetPasswords());

    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);
});

test('password reset otp can be requested for each portal user type', function (string $phone) {
    $user = User::query()->where('phone', $phone)->firstOrFail();

    $response = $this->post(route('password.email'), ['phone' => $phone]);

    $response->assertRedirect(route('password.otp'))->assertSessionHas('status');

    /** @var FakeSmsSender $sms */
    $sms = app(FakeSmsSender::class);
    expect($sms->lastCode())->not->toBeNull()
        ->and($sms->messages[array_key_last($sms->messages)]['to'] ?? null)->toBe($user->phone);
})->with([
    'platform' => [UserSeeder::PLATFORM_SUPER_ADMIN_PHONE],
    'branch' => [UserSeeder::BRANCH_1_SUPER_ADMIN_PHONE],
    'driver' => [UserSeeder::DRIVER_PHONE],
    'customer' => [UserSeeder::CUSTOMER_PHONE],
]);

test('portal users can reset password via otp and log in with the new password', function (string $phone, string $guard, string $dashboardPath) {
    $user = User::query()->where('phone', $phone)->firstOrFail();
    $newPassword = 'new-secure-password';

    $this->post(route('password.email'), ['phone' => $phone])
        ->assertRedirect(route('password.otp'));

    /** @var FakeSmsSender $sms */
    $sms = app(FakeSmsSender::class);

    $this->post(route('password.otp.store'), [
        'phone' => $phone,
        'code' => $sms->lastCode(),
    ])->assertRedirect(route('password.reset'));

    $this->post(route('password.update'), [
        'phone' => $phone,
        'password' => $newPassword,
        'password_confirmation' => $newPassword,
    ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('login'))
        ->assertSessionHas('status', __('passwords.reset'));

    $this->assertGuest($guard);
    expect(Hash::check($newPassword, $user->fresh()->password))->toBeTrue();

    $this->post(route('login.store'), [
        'phone' => $phone,
        'password' => $newPassword,
    ])->assertRedirect($dashboardPath);

    $this->assertAuthenticated($guard);
})->with([
    'platform' => [UserSeeder::PLATFORM_SUPER_ADMIN_PHONE, 'platform', '/platform/dashboard'],
    'branch' => [UserSeeder::BRANCH_1_SUPER_ADMIN_PHONE, 'branch', '/branch/downtown/dashboard'],
    'driver' => [UserSeeder::DRIVER_PHONE, 'driver', '/driver/dashboard'],
    'customer' => [UserSeeder::CUSTOMER_PHONE, 'customer', '/dashboard'],
]);

test('unknown phone still receives generic success without sending an otp', function () {
    /** @var FakeSmsSender $sms */
    $sms = app(FakeSmsSender::class);
    $sms->reset();

    $response = $this->post(route('password.email'), ['phone' => '0412987654']);

    $response->assertRedirect(route('password.otp'))->assertSessionHas('status');
    expect($sms->lastCode())->toBeNull();
});

test('inactive users receive generic success without sending an otp', function () {
    /** @var FakeSmsSender $sms */
    $sms = app(FakeSmsSender::class);
    $sms->reset();

    User::query()->where('phone', UserSeeder::PLATFORM_SUPER_ADMIN_PHONE)->update(['is_active' => false]);

    $response = $this->post(route('password.email'), [
        'phone' => UserSeeder::PLATFORM_SUPER_ADMIN_PHONE,
    ]);

    $response->assertRedirect(route('password.otp'))->assertSessionHas('status');
    expect($sms->lastCode())->toBeNull();
});

test('password cannot be reset without a verified otp session', function () {
    $user = User::query()->where('phone', UserSeeder::PLATFORM_SUPER_ADMIN_PHONE)->firstOrFail();

    $response = $this->post(route('password.update'), [
        'phone' => $user->phone,
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);

    $response->assertSessionHasErrors('phone');
});

test('fourth forgot password request within ten minutes is rate limited', function () {
    for ($attempt = 0; $attempt < 3; $attempt++) {
        $this->post(route('password.email'), [
            'phone' => UserSeeder::PLATFORM_SUPER_ADMIN_PHONE,
        ]);
    }

    $response = $this->post(route('password.email'), [
        'phone' => UserSeeder::PLATFORM_SUPER_ADMIN_PHONE,
    ]);

    $response->assertTooManyRequests();
});
