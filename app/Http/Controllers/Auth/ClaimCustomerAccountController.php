<?php

namespace App\Http\Controllers\Auth;

use App\Enums\CustomerRegistrationStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Customer\CustomerClaimService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ClaimCustomerAccountController extends Controller
{
    public const SESSION_KEY = 'customer_claim_phone';

    public function create(Request $request): RedirectResponse|Response
    {
        $customer = $this->customer($request);

        if ($customer === null) {
            return redirect()->route('register');
        }

        return Inertia::render('auth/claim-account', [
            'phone' => $customer->phone,
        ]);
    }

    public function store(Request $request, CustomerClaimService $claims): RedirectResponse
    {
        $customer = $this->customer($request);

        if ($customer === null) {
            throw ValidationException::withMessages([
                'code' => __('Start again from registration.'),
            ]);
        }

        $data = $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $user = $claims->complete($customer, $data['code']);

        $request->session()->forget(self::SESSION_KEY);

        Auth::guard('customer')->login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    private function customer(Request $request): ?User
    {
        $phone = $request->session()->get(self::SESSION_KEY);

        if (! is_string($phone) || $phone === '') {
            return null;
        }

        return User::query()
            ->where('phone', $phone)
            ->where('registration_status', CustomerRegistrationStatus::Provisional)
            ->first();
    }
}
