<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\PortalAuth;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePortalGuard
{
    /**
     * Ensure the request is authenticated on the required portal guard.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string $guard): Response
    {
        if (! in_array($guard, PortalAuth::PORTAL_GUARDS, true)) {
            abort(500, 'Invalid portal guard.');
        }

        if (! Auth::guard($guard)->check()) {
            return redirect()->guest(route('login'));
        }

        Auth::shouldUse($guard);

        /** @var User $user */
        $user = Auth::guard($guard)->user();

        if (! $user->is_active) {
            Auth::guard($guard)->logout();

            return redirect()
                ->route('login')
                ->withErrors(['email' => __('auth.failed')]);
        }

        return $next($request);
    }
}
