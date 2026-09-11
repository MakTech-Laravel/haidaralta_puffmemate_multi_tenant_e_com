<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\StoreRegisteredUserRequest;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Contracts\CreatesNewUsers;

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
        event(new Registered($user = $creator->create($request->validated())));

        Auth::guard('customer')->login($user, $request->boolean('remember'));

        $request->session()->regenerate();

        return redirect()->route('phone.verification.notice');
    }
}
