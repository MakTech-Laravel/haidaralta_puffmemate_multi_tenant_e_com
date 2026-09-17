<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;

class AccountController extends Controller
{
    /**
     * Display the customer account settings page.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('customer/account/index', [
            'mustVerifyPhone' => $user?->phone_verified_at === null,
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
            'canManageTwoFactor' => Features::canManageTwoFactorAuthentication(),
            'twoFactorEnabled' => Features::canManageTwoFactorAuthentication()
                ? $user->hasEnabledTwoFactorAuthentication()
                : false,
        ]);
    }
}
