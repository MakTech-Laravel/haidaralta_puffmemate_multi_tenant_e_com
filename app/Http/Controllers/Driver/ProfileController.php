<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\DriverPayload;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Field profile: identity, vehicle, branches, and roster for the signed-in driver.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $driver = DriverPayload::resolveDriver($user);

        return Inertia::render('driver/profile/index', [
            'driver' => DriverPayload::detail($driver),
        ]);
    }
}
