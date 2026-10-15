<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\UpdateCustomerDeliveryAddressRequest;
use App\Http\Requests\Customer\UpdateCustomerPasswordRequest;
use App\Http\Requests\Customer\UpdateCustomerProfileRequest;
use App\Http\Requests\Settings\TwoFactorAuthenticationRequest;
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
            'deliveryAddress' => $this->accounts->deliveryAddress($user),
        ]);
    }

    /**
     * Persist identity and avatar.
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
     * Save the single delivery address checkout pre-fills.
     */
    public function updateDeliveryAddress(UpdateCustomerDeliveryAddressRequest $request): RedirectResponse
    {
        $this->accounts->saveDeliveryAddress(
            $this->signedInCustomer($request),
            $request->validated(),
        );

        Toast::success(__('Delivery address saved.'));

        return back();
    }

    /**
     * Forget the saved delivery address so checkout starts empty.
     */
    public function destroyDeliveryAddress(Request $request): RedirectResponse
    {
        $this->accounts->removeDeliveryAddress($this->signedInCustomer($request));

        Toast::success(__('Delivery address removed.'));

        return back();
    }

    private function signedInCustomer(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        return $user;
    }
}
