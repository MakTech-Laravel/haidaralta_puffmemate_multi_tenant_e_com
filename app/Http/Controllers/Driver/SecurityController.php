<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\DriverPayload;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SecurityController extends Controller
{
    /**
     * Static security status for the signed-in driver.
     */
    public function index(Request $request): Response
    {
        $driver = $this->signedInDriver($request);

        return Inertia::render('driver/security/index', [
            'phone' => $driver->phone,
            'phoneVerified' => $driver->phone_verified_at !== null,
            'twoFactorEnabled' => $driver->hasEnabledTwoFactorAuthentication(),
            'passkeyCount' => $driver->passkeys()->count(),
        ]);
    }

    private function signedInDriver(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        return DriverPayload::resolveDriver($user);
    }
}
