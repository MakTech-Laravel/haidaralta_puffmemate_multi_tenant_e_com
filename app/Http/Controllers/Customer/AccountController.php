<?php

namespace App\Http\Controllers\Customer;

use App\Enums\AustralianState;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\UpdateCustomerBillingAddressRequest;
use App\Http\Requests\Customer\UpdateCustomerPasswordRequest;
use App\Http\Requests\Customer\UpdateCustomerProfileRequest;
use App\Http\Requests\Settings\TwoFactorAuthenticationRequest;
use App\Models\CustomerAddress;
use App\Models\User;
use App\Services\Customer\CustomerAccountService;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;

class AccountController extends Controller
{
    public function __construct(private CustomerAccountService $accounts) {}

    /**
     * Display the customer account settings page.
     */
    public function index(TwoFactorAuthenticationRequest $request): Response
    {
        $user = $this->signedInCustomer($request);
        $user->load(['shippingAddress', 'billingAddress']);

        $canManageTwoFactor = Features::canManageTwoFactorAuthentication();

        if ($canManageTwoFactor) {
            $request->ensureStateIsValid();
        }

        return Inertia::render('customer/account/index', [
            'mustVerifyPhone' => $user->phone_verified_at === null,
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
            'canManageTwoFactor' => $canManageTwoFactor,
            'twoFactorEnabled' => $canManageTwoFactor
                ? $user->hasEnabledTwoFactorAuthentication()
                : false,
            'requiresConfirmation' => $canManageTwoFactor
                && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm'),
            'shippingAddress' => $this->shippingPayload($user->shippingAddress),
            'billingAddress' => $this->billingPayload($user->billingAddress),
            'regions' => AustralianState::options(),
        ]);
    }

    /**
     * Persist identity, avatar, and shipping address.
     */
    public function updateProfile(UpdateCustomerProfileRequest $request): RedirectResponse
    {
        $this->accounts->updateProfile(
            $this->signedInCustomer($request),
            $request->safe()->except(['avatar']),
            avatar: $request->file('avatar'),
        );

        Toast::success(__('Profile updated.'));

        return back();
    }

    /**
     * Persist a new password for the signed-in customer.
     */
    public function updatePassword(UpdateCustomerPasswordRequest $request): RedirectResponse
    {
        $this->accounts->updatePassword(
            $this->signedInCustomer($request),
            $request->validated('password'),
        );

        Toast::success(__('Password updated.'));

        return back();
    }

    /**
     * Persist the customer's billing address.
     */
    public function updateBillingAddress(UpdateCustomerBillingAddressRequest $request): RedirectResponse
    {
        $this->accounts->updateBillingAddress(
            $this->signedInCustomer($request),
            $request->validated(),
        );

        Toast::success(__('Billing address updated.'));

        return back();
    }

    private function signedInCustomer(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        return $user;
    }

    /**
     * @return array{region: string|null, city: string|null, zip: string|null, address: string|null}|null
     */
    private function shippingPayload(?CustomerAddress $address): ?array
    {
        if ($address === null) {
            return null;
        }

        return [
            'region' => $address->region?->value,
            'city' => $address->city,
            'zip' => $address->zip,
            'address' => $address->address,
        ];
    }

    /**
     * @return array{name: string|null, address: string|null, region: string|null, city: string|null, zip: string|null, email: string|null, phone: string|null}|null
     */
    private function billingPayload(?CustomerAddress $address): ?array
    {
        if ($address === null) {
            return null;
        }

        return [
            'name' => $address->name,
            'address' => $address->address,
            'region' => $address->region?->value,
            'city' => $address->city,
            'zip' => $address->zip,
            'email' => $address->email,
            'phone' => $address->phone,
        ];
    }
}
