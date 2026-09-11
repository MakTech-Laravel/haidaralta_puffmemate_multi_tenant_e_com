<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\PortalAuth;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAnyPortalGuard
{
    /**
     * Ensure the request is authenticated on any portal guard, then set it as default.
     *
     * Shared routes (settings) must not use plain `auth` (default `web`) — portal
     * users log in on platform/branch/driver/customer guards only.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $guard = PortalAuth::activeGuard();

        if ($guard === null) {
            return redirect()->guest(route('login'));
        }

        Auth::shouldUse($guard);

        /** @var User $user */
        $user = Auth::guard($guard)->user();

        if (! $user->is_active) {
            Auth::guard($guard)->logout();

            return redirect()
                ->route('login')
                ->withErrors(['phone' => __('auth.failed')]);
        }

        return $next($request);
    }
}
