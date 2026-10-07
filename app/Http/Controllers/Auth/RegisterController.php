<?php

namespace App\Http\Controllers\Auth;

use App\Enums\CustomerRegistrationStatus;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\StoreRegisteredUserRequest;
use App\Models\User;
use App\Services\Customer\CustomerClaimService;
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
    public function store(StoreRegisteredUserRequest $request, CreatesNewUsers $creator, CustomerClaimService $claims): RedirectResponse
    {
        $provisional = User::query()
            ->where('phone', $request->string('phone')->value())
            ->where('user_type', UserType::CUSTOMER)
            ->where('registration_status', CustomerRegistrationStatus::Provisional)
            ->first();

        if ($provisional !== null) {
            $claims->begin(
                $provisional,
                $request->string('name')->value(),
                $request->string('password')->value(),
            );

            $request->session()->put(ClaimCustomerAccountController::SESSION_KEY, $provisional->phone);

            return redirect()->route('register.claim');
        }

        event(new Registered($user = $creator->create($request->validated())));

        Auth::guard('customer')->login($user, $request->boolean('remember'));

        $request->session()->regenerate();

        return redirect()->route('phone.verification.notice');
    }
}
