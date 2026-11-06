<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Http\Requests\Driver\StoreDriverLocationRequest;
use App\Models\User;
use App\Services\Driver\DriverLocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class LocationController extends Controller
{
    public function __construct(private readonly DriverLocationService $locations) {}

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
            'reason' => $outcome->reason,
        ]);
    }
}
