<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\VerifyPhoneController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Laravel\Fortify\Http\Controllers\ConfirmablePasswordController;
use Laravel\Fortify\Http\Controllers\ConfirmedPasswordStatusController;
use Laravel\Fortify\Http\Controllers\ConfirmedTwoFactorAuthenticationController;
use Laravel\Fortify\Http\Controllers\PasswordController;
use Laravel\Fortify\Http\Controllers\ProfileInformationController;
use Laravel\Fortify\Http\Controllers\RecoveryCodeController;
use Laravel\Fortify\Http\Controllers\TwoFactorAuthenticatedSessionController;
use Laravel\Fortify\Http\Controllers\TwoFactorAuthenticationController;
use Laravel\Fortify\Http\Controllers\TwoFactorQrCodeController;
use Laravel\Fortify\Http\Controllers\TwoFactorSecretKeyController;
use Laravel\Fortify\RoutePath;
use Laravel\Passkeys\Http\Controllers\PasskeyConfirmationController;
use Laravel\Passkeys\Http\Controllers\PasskeyLoginController;
use Laravel\Passkeys\Http\Controllers\PasskeyRegistrationController;

Route::group(['middleware' => config('fortify.middleware', ['web'])], function () {
    $enableViews = config('fortify.views', true);

    // Authentication (custom multi-guard login/logout)...
    if ($enableViews) {
        Route::get(RoutePath::for('login', '/login'), [LoginController::class, 'create'])
            ->name('login');
    }

    $limiter = config('fortify.limiters.login');
    $twoFactorLimiter = config('fortify.limiters.two-factor');
    $passkeyLimiter = config('fortify.limiters.passkeys');
    $verificationLimiter = config('fortify.limiters.verification', '6,1');

    Route::post(RoutePath::for('login', '/login'), [LoginController::class, 'store'])
        ->middleware(array_filter([
            $limiter ? 'throttle:'.$limiter : null,
        ]))->name('login.store');

    Route::post(RoutePath::for('logout', '/logout'), [LoginController::class, 'destroy'])
        ->name('logout');

    // Password Reset via phone OTP...
    if (Features::enabled(Features::resetPasswords())) {
        if ($enableViews) {
            Route::get(RoutePath::for('password.request', '/forgot-password'), [ForgotPasswordController::class, 'create'])
                ->name('password.request');

            Route::get('/forgot-password/verify', [ResetPasswordController::class, 'createOtp'])
                ->name('password.otp');

            Route::get('/reset-password', [ResetPasswordController::class, 'create'])
                ->name('password.reset');
        }

        Route::post(RoutePath::for('password.email', '/forgot-password'), [ForgotPasswordController::class, 'store'])
            ->middleware(['throttle:password-reset'])
            ->name('password.email');

        Route::post('/forgot-password/verify', [ResetPasswordController::class, 'storeOtp'])
            ->middleware(['throttle:password-reset'])
            ->name('password.otp.store');

        Route::post(RoutePath::for('password.update', '/reset-password'), [ResetPasswordController::class, 'store'])
            ->name('password.update');
    }

    // Registration...
    if (Features::enabled(Features::registration())) {
        if ($enableViews) {
            Route::get(RoutePath::for('register', '/register'), [RegisterController::class, 'create'])
                ->name('register');
        }

        Route::post(RoutePath::for('register', '/register'), [RegisterController::class, 'store'])
            ->middleware('precognitive')
            ->name('register.store');
    }

    // Phone Verification...
    if ($enableViews) {
        Route::get('/phone/verify', [VerifyPhoneController::class, 'notice'])
            ->middleware(['ensure.any.portal'])
            ->name('phone.verification.notice');
    }

    Route::post('/phone/verification-notification', [VerifyPhoneController::class, 'send'])
        ->middleware(['ensure.any.portal', 'throttle:'.$verificationLimiter])
        ->name('phone.verification.send');

    Route::post('/phone/verify', [VerifyPhoneController::class, 'verify'])
        ->middleware(['ensure.any.portal', 'throttle:'.$verificationLimiter])
        ->name('phone.verification.verify');

    // Account routes: portal guards only (not fortify.guard / web).
    $portalAuthMiddleware = ['ensure.any.portal'];

    // Profile Information...
    if (Features::enabled(Features::updateProfileInformation())) {
        Route::put(RoutePath::for('user-profile-information.update', '/user/profile-information'), [ProfileInformationController::class, 'update'])
            ->middleware($portalAuthMiddleware)
            ->name('user-profile-information.update');
    }

    // Passwords...
    if (Features::enabled(Features::updatePasswords())) {
        Route::put(RoutePath::for('user-password.update', '/user/password'), [PasswordController::class, 'update'])
            ->middleware($portalAuthMiddleware)
            ->name('user-password.update');
    }

    // Password Confirmation...
    if ($enableViews) {
        Route::get(RoutePath::for('password.confirm', '/user/confirm-password'), [ConfirmablePasswordController::class, 'show'])
            ->middleware($portalAuthMiddleware)
            ->name('password.confirm');
    }

    Route::get(RoutePath::for('password.confirmation', '/user/confirmed-password-status'), [ConfirmedPasswordStatusController::class, 'show'])
        ->middleware($portalAuthMiddleware)
        ->name('password.confirmation');

    Route::post(RoutePath::for('password.confirm', '/user/confirm-password'), [ConfirmablePasswordController::class, 'store'])
        ->middleware($portalAuthMiddleware)
        ->name('password.confirm.store');

    // Two Factor Authentication...
    if (Features::enabled(Features::twoFactorAuthentication())) {
        if ($enableViews) {
            Route::get(RoutePath::for('two-factor.login', '/two-factor-challenge'), [TwoFactorAuthenticatedSessionController::class, 'create'])
                ->middleware(['guest:'.config('fortify.guard')])
                ->name('two-factor.login');
        }

        Route::post(RoutePath::for('two-factor.login', '/two-factor-challenge'), [TwoFactorAuthenticatedSessionController::class, 'store'])
            ->middleware(array_filter([
                'guest:'.config('fortify.guard'),
                $twoFactorLimiter ? 'throttle:'.$twoFactorLimiter : null,
            ]))->name('two-factor.login.store');

        $twoFactorMiddleware = Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword')
            ? [...$portalAuthMiddleware, 'password.confirm']
            : $portalAuthMiddleware;

        Route::post(RoutePath::for('two-factor.enable', '/user/two-factor-authentication'), [TwoFactorAuthenticationController::class, 'store'])
            ->middleware($twoFactorMiddleware)
            ->name('two-factor.enable');

        Route::post(RoutePath::for('two-factor.confirm', '/user/confirmed-two-factor-authentication'), [ConfirmedTwoFactorAuthenticationController::class, 'store'])
            ->middleware($twoFactorMiddleware)
            ->name('two-factor.confirm');

        Route::delete(RoutePath::for('two-factor.disable', '/user/two-factor-authentication'), [TwoFactorAuthenticationController::class, 'destroy'])
            ->middleware($twoFactorMiddleware)
            ->name('two-factor.disable');

        Route::get(RoutePath::for('two-factor.qr-code', '/user/two-factor-qr-code'), [TwoFactorQrCodeController::class, 'show'])
            ->middleware($twoFactorMiddleware)
            ->name('two-factor.qr-code');

        Route::get(RoutePath::for('two-factor.secret-key', '/user/two-factor-secret-key'), [TwoFactorSecretKeyController::class, 'show'])
            ->middleware($twoFactorMiddleware)
            ->name('two-factor.secret-key');

        Route::get(RoutePath::for('two-factor.recovery-codes', '/user/two-factor-recovery-codes'), [RecoveryCodeController::class, 'index'])
            ->middleware($twoFactorMiddleware)
            ->name('two-factor.recovery-codes');

        Route::post(RoutePath::for('two-factor.recovery-codes', '/user/two-factor-recovery-codes'), [RecoveryCodeController::class, 'store'])
            ->middleware($twoFactorMiddleware)
            ->name('two-factor.regenerate-recovery-codes');
    }

    // Passkeys...
    if (Features::enabled(Features::passkeys())) {
        $throttle = $passkeyLimiter ? ['throttle:'.$passkeyLimiter] : [];

        $passkeyAuthMiddleware = $portalAuthMiddleware;

        $passkeyMiddleware = config('fortify-options.passkeys.confirmPassword', true)
            ? [...$passkeyAuthMiddleware, 'password.confirm']
            : $passkeyAuthMiddleware;

        $passkeyGuestMiddleware = ['guest:'.config('fortify.guard'), ...$throttle];
        $passkeyConfirmMiddleware = [...$passkeyAuthMiddleware, ...$throttle];
        $passkeyManageMiddleware = [...$passkeyMiddleware, ...$throttle];

        Route::get(RoutePath::for('passkey.login-options', '/passkeys/login/options'), [PasskeyLoginController::class, 'index'])
            ->middleware($passkeyGuestMiddleware)
            ->name('passkey.login-options');

        Route::post(RoutePath::for('passkey.login', '/passkeys/login'), [PasskeyLoginController::class, 'store'])
            ->middleware($passkeyGuestMiddleware)
            ->name('passkey.login');

        Route::get(RoutePath::for('passkey.confirm-options', '/passkeys/confirm/options'), [PasskeyConfirmationController::class, 'index'])
            ->middleware($passkeyConfirmMiddleware)
            ->name('passkey.confirm-options');

        Route::post(RoutePath::for('passkey.confirm', '/passkeys/confirm'), [PasskeyConfirmationController::class, 'store'])
            ->middleware($passkeyConfirmMiddleware)
            ->name('passkey.confirm');

        Route::get(RoutePath::for('passkey.registration-options', '/user/passkeys/options'), [PasskeyRegistrationController::class, 'index'])
            ->middleware($passkeyManageMiddleware)
            ->name('passkey.registration-options');

        Route::post(RoutePath::for('passkey.store', '/user/passkeys'), [PasskeyRegistrationController::class, 'store'])
            ->middleware($passkeyManageMiddleware)
            ->name('passkey.store');

        Route::delete(RoutePath::for('passkey.destroy', '/user/passkeys/{passkey}'), [PasskeyRegistrationController::class, 'destroy'])
            ->middleware($passkeyMiddleware)
            ->name('passkey.destroy');
    }
});
