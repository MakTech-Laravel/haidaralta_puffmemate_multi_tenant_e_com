<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Support\PortalAuth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;

class LoginController extends Controller
{
    /**
     * Show the login page, or redirect if already authenticated on a portal guard.
     */
    public function create(Request $request): RedirectResponse|Response
    {
        $activeGuard = PortalAuth::activeGuard();

        if ($activeGuard !== null) {
            return redirect(PortalAuth::dashboardPath(Auth::guard($activeGuard)->user()));
        }

        return Inertia::render('auth/login', [
            'canResetPassword' => Features::enabled(Features::resetPasswords()),
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $activeGuard = PortalAuth::activeGuard();

        if ($activeGuard === null) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'phone' => [__('auth.failed')],
            ]);
        }

        $user = Auth::guard($activeGuard)->user();
        $user->loadMissing('branch');

        return redirect()->intended(
            PortalAuth::dashboardPath($user),
        );
    }

    /**
     * Log the user out of the currently active portal guard only.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $activeGuard = PortalAuth::activeGuard();

        if ($activeGuard !== null) {
            Auth::guard($activeGuard)->logout();
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
