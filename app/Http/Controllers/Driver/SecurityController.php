<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Http\Requests\Driver\UpdateDriverPasswordRequest;
use App\Http\Requests\Settings\TwoFactorAuthenticationRequest;
use App\Models\User;
use App\Services\Driver\DriverService;
use App\Support\DriverPayload;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;

class SecurityController extends Controller
{
    public function __construct(private DriverService $drivers) {}

    /**
     * Field security page for the signed-in driver.
     */
    public function index(TwoFactorAuthenticationRequest $request): Response
    {
        $driver = $this->signedInDriver($request);
        $canManageTwoFactor = Features::canManageTwoFactorAuthentication();

        if ($canManageTwoFactor) {
            $request->ensureStateIsValid();
        }

        return Inertia::render('driver/security/index', [
            'phone' => $driver->phone,
            'phoneVerified' => $driver->phone_verified_at !== null,
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
            'canManageTwoFactor' => $canManageTwoFactor,
            'twoFactorEnabled' => $canManageTwoFactor
                ? $driver->hasEnabledTwoFactorAuthentication()
                : false,
            'requiresConfirmation' => $canManageTwoFactor
                && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm'),
            'passkeyCount' => $driver->passkeys()->count(),
        ]);
    }

    /**
     * Persist a new password for the signed-in driver.
     */
    public function updatePassword(UpdateDriverPasswordRequest $request): RedirectResponse
    {
        $this->drivers->updatePassword(
            $this->signedInDriver($request),
            $request->validated('password'),
        );

        Toast::success(__('Password updated.'));

        return back();
    }

    private function signedInDriver(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        return DriverPayload::resolveDriver($user);
    }
}
