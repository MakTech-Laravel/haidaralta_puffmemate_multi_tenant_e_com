<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\PortalAuth;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Fortify\Fortify;

class RegisterController extends Controller
{
    /**
     * Show the registration page.
     */
    public function create(): Response
    {
        return Inertia::render('auth/register', [
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ]);
    }

    /**
     * Handle a registration request for a new customer account.
     */
    public function store(Request $request, CreatesNewUsers $creator): RedirectResponse
    {
        if (config('fortify.lowercase_usernames') && $request->has(Fortify::username())) {
            $request->merge([
                Fortify::username() => Str::lower($request->string(Fortify::username())->value()),
            ]);
        }

        event(new Registered($user = $creator->create($request->all())));

        Auth::guard('customer')->login($user, $request->boolean('remember'));

        $request->session()->regenerate();

        return redirect()->intended(PortalAuth::dashboardPath($user));
    }
}
