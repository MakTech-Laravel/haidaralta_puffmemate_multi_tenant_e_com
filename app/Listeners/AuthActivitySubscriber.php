<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Events\Dispatcher;
use Laravel\Fortify\Events\TwoFactorAuthenticationConfirmed;
use Laravel\Fortify\Events\TwoFactorAuthenticationDisabled;
use Laravel\Fortify\Events\TwoFactorAuthenticationEnabled;
use Laravel\Fortify\Events\TwoFactorAuthenticationFailed;

class AuthActivitySubscriber
{
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(Login::class, $this->onLogin(...));
        $events->listen(Logout::class, $this->onLogout(...));
        $events->listen(Failed::class, $this->onFailed(...));
        $events->listen(Registered::class, $this->onRegistered(...));
        $events->listen(PasswordReset::class, $this->onPasswordReset(...));
        $events->listen(Verified::class, $this->onVerified(...));

        if (class_exists(TwoFactorAuthenticationEnabled::class)) {
            $events->listen(TwoFactorAuthenticationEnabled::class, $this->onTwoFactorEnabled(...));
            $events->listen(TwoFactorAuthenticationDisabled::class, $this->onTwoFactorDisabled(...));
            $events->listen(TwoFactorAuthenticationConfirmed::class, $this->onTwoFactorConfirmed(...));
            $events->listen(TwoFactorAuthenticationFailed::class, $this->onTwoFactorFailed(...));
        }
    }

    public function onLogin(Login $event): void
    {
        audit(
            description: 'User logged in',
            subject: $event->user,
            properties: [
                'guard' => $event->guard,
                'remember' => $event->remember,
            ],
            event: 'login',
            logName: 'auth',
        );
    }

    public function onLogout(Logout $event): void
    {
        if ($event->user === null) {
            return;
        }

        audit(
            description: 'User logged out',
            subject: $event->user,
            properties: [
                'guard' => $event->guard,
            ],
            event: 'logout',
            logName: 'auth',
        );
    }

    public function onFailed(Failed $event): void
    {
        audit(
            description: 'Login failed',
            subject: $event->user,
            properties: [
                'guard' => $event->guard,
                'email' => $event->credentials['email'] ?? null,
            ],
            event: 'failed',
            logName: 'auth',
        );
    }

    public function onRegistered(Registered $event): void
    {
        audit(
            description: 'User registered',
            subject: $event->user,
            event: 'registered',
            logName: 'auth',
        );
    }

    public function onPasswordReset(PasswordReset $event): void
    {
        audit(
            description: 'Password reset',
            subject: $event->user,
            event: 'password_reset',
            logName: 'auth',
        );
    }

    public function onVerified(Verified $event): void
    {
        audit(
            description: 'Email verified',
            subject: $event->user,
            event: 'verified',
            logName: 'auth',
        );
    }

    public function onTwoFactorEnabled(TwoFactorAuthenticationEnabled $event): void
    {
        audit(
            description: 'Two-factor authentication enabled',
            subject: $event->user,
            event: 'two_factor_enabled',
            logName: 'auth',
        );
    }

    public function onTwoFactorDisabled(TwoFactorAuthenticationDisabled $event): void
    {
        audit(
            description: 'Two-factor authentication disabled',
            subject: $event->user,
            event: 'two_factor_disabled',
            logName: 'auth',
        );
    }

    public function onTwoFactorConfirmed(TwoFactorAuthenticationConfirmed $event): void
    {
        audit(
            description: 'Two-factor authentication confirmed',
            subject: $event->user,
            event: 'two_factor_confirmed',
            logName: 'auth',
        );
    }

    public function onTwoFactorFailed(TwoFactorAuthenticationFailed $event): void
    {
        audit(
            description: 'Two-factor authentication failed',
            subject: $event->user,
            event: 'two_factor_failed',
            logName: 'auth',
        );
    }
}
