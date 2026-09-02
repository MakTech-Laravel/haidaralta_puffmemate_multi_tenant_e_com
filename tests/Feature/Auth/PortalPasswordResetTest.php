<?php

/**
 * Authoritative multi-guard password reset feature tests (Phases 4–7).
 * Starter-kit smoke coverage remains in PasswordResetTest.php.
 */

use App\Models\User;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::resetPasswords());

    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);
});

test('password reset link can be requested for each portal user type', function (string $email) {
    Notification::fake();

    $user = User::query()->where('email', $email)->firstOrFail();

    $response = $this->post(route('password.email'), ['email' => $email]);

    $response->assertRedirect()->assertSessionHas('status', __('passwords.sent'));
    Notification::assertSentTo($user, ResetPassword::class);
})->with([
    'platform' => ['platform@test.com'],
    'branch' => ['branch@test.com'],
    'driver' => ['driver@test.com'],
    'customer' => ['customer@test.com'],
]);

test('portal users can reset password and log in with the new password', function (string $email, string $guard, string $dashboardPath) {
    Notification::fake();

    $user = User::query()->where('email', $email)->firstOrFail();
    $newPassword = 'new-secure-password';

    $this->post(route('password.email'), ['email' => $email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user, $newPassword, $guard, $dashboardPath) {
        $response = $this->post(route('password.update'), [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', __('passwords.reset'));

        $this->assertGuest($guard);

        $loginResponse = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => $newPassword,
        ]);

        $loginResponse->assertRedirect($dashboardPath);
        $this->assertAuthenticated($guard);

        return true;
    });
})->with([
    'platform' => ['platform@test.com', 'platform', '/platform/dashboard'],
    'branch' => ['branch@test.com', 'branch', '/branch/downtown/dashboard'],
    'driver' => ['driver@test.com', 'driver', '/driver/dashboard'],
    'customer' => ['customer@test.com', 'customer', '/dashboard'],
]);

test('unknown email still receives generic success without sending a notification', function () {
    Notification::fake();

    $response = $this->post(route('password.email'), ['email' => 'missing@example.com']);

    $response->assertRedirect()->assertSessionHas('status', __('passwords.sent'));
    Notification::assertNothingSent();
});

test('inactive users receive generic success without sending a notification', function () {
    Notification::fake();

    User::query()->where('email', 'platform@test.com')->update(['is_active' => false]);

    $response = $this->post(route('password.email'), ['email' => 'platform@test.com']);

    $response->assertRedirect()->assertSessionHas('status', __('passwords.sent'));
    Notification::assertNothingSent();
});

test('password cannot be reset with an invalid token', function () {
    $user = User::query()->where('email', 'platform@test.com')->firstOrFail();

    $response = $this->post(route('password.update'), [
        'token' => 'invalid-token',
        'email' => $user->email,
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);

    $response->assertSessionHasErrors('email');
});

test('fourth forgot password request within ten minutes is rate limited', function () {
    for ($attempt = 0; $attempt < 3; $attempt++) {
        $this->post(route('password.email'), ['email' => 'platform@test.com']);
    }

    $response = $this->post(route('password.email'), ['email' => 'platform@test.com']);

    $response->assertTooManyRequests();
});

test('reset password notification uses the shared reset url with email query', function () {
    Notification::fake();

    $user = User::query()->where('email', 'driver@test.com')->firstOrFail();

    $this->post(route('password.email'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        $url = $notification->toMail($user)->actionUrl;

        expect($url)->toContain('/reset-password/'.$notification->token)
            ->and($url)->toContain('email='.urlencode($user->email));

        return true;
    });
});
