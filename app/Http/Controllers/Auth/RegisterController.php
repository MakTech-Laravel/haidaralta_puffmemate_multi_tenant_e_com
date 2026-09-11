<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\StoreRegisteredUserRequest;
use App\Support\PortalAuth;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
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
    public function store(StoreRegisteredUserRequest $request, CreatesNewUsers $creator): RedirectResponse
    {
        $input = $request->validated();

        if (config('fortify.lowercase_usernames') && array_key_exists(Fortify::username(), $input)) {
            $input[Fortify::username()] = Str::lower((string) $input[Fortify::username()]);
        }

        event(new Registered($user = $creator->create($input)));

        Auth::guard('customer')->login($user, $request->boolean('remember'));

        $request->session()->regenerate();

        return redirect()->intended(PortalAuth::dashboardPath($user));
    }
}
