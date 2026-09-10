<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\PortalAuth;
use Closure;
use Illuminate\Database\QueryException;
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

        try {
            if (! Auth::guard($guard)->check()) {
                return redirect()->guest(route('login'));
            }

            Auth::shouldUse($guard);

            /** @var User $user */
            $user = Auth::guard($guard)->user();
        } catch (QueryException $exception) {
            // Schema may be mid-wipe during database restore; avoid Ignition 500 pages.
            Auth::guard($guard)->logout();

            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            return redirect()
                ->guest(route('login'))
                ->withErrors([
                    'email' => __('The application database is temporarily unavailable. Please try again in a moment.'),
                ]);
        }

        if (! $user->is_active) {
            Auth::guard($guard)->logout();

            return redirect()
                ->route('login')
                ->withErrors(['email' => __('auth.failed')]);
        }

        return $next($request);
    }
}
