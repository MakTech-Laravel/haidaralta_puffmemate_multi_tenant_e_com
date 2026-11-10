<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Http\Requests\Driver\StoreDriverLocationRequest;
use App\Models\User;
use App\Services\Driver\DriverLocationService;
use App\Services\Driver\DriverTrackingGate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class LocationController extends Controller
{
    public function __construct(
        private readonly DriverLocationService $locations,
        private readonly DriverTrackingGate $gate,
    ) {}

    /**
     * Full-screen prompt shown when a driver on duty has no fresh position. It asks the browser
     * for location, sends the first reading, then returns the driver to where they were going.
     */
    public function required(Request $request): InertiaResponse|RedirectResponse
    {
        /** @var User $driver */
        $driver = $request->user();

        if (! $this->gate->isBlocked($driver)) {
            return redirect()->to($request->session()->pull('url.intended', route('driver.dashboard')));
        }

        return Inertia::render('driver/location-required', [
            'intended' => $request->session()->get('url.intended', route('driver.dashboard', absolute: false)),
        ]);
    }

    /**
     * 204 means the driver is not on duty, so the app should stop sending readings.
     * 200 means keep sending; `accepted` is false when this reading was ignored.
     */
    public function store(StoreDriverLocationRequest $request): JsonResponse|Response
    {
        /** @var User $driver */
        $driver = $request->user();

        $outcome = $this->locations->record($driver, $request->reading());

        if (! $outcome->tracking) {
            return response()->noContent();
        }

        return response()->json([
            'tracking' => true,
            'accepted' => $outcome->accepted,
            'coarse' => $outcome->coarse,
            'reason' => $outcome->reason,
        ]);
    }
}
