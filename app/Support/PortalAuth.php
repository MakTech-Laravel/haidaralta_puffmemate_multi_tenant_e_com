<?php

namespace App\Support;

use App\Enums\UserType;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Portal guard detection and post-login redirect paths.
 */
final class PortalAuth
{
    /**
     * @var list<string>
     */
    public const PORTAL_GUARDS = [
        'platform',
        'branch',
        'driver',
        'customer',
    ];

    /**
     * The name of the portal guard currently authenticated, if any.
     */
    public static function activeGuard(): ?string
    {
        foreach (self::PORTAL_GUARDS as $guard) {
            if (Auth::guard($guard)->check()) {
                return $guard;
            }
        }

        return null;
    }

    /**
     * Dashboard path for the given user after login or when already authenticated.
     */
    public static function dashboardPath(User $user): string
    {
        return match ($user->user_type) {
            UserType::PLATFORM => '/platform/dashboard',
            UserType::BRANCH => '/branch/'.$user->branch?->slug.'/dashboard',
            UserType::DRIVER => '/driver/dashboard',
            UserType::CUSTOMER => '/dashboard',
        };
    }
}
