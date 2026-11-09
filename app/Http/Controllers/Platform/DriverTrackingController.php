<?php

namespace App\Http\Controllers\Platform;

use App\Events\DriverLocationUpdated;
use App\Http\Controllers\Controller;
use App\Services\Driver\DriverMapWatchers;
use App\Services\Driver\DriverTrackingBoard;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class DriverTrackingController extends Controller
{
    public function __construct(
        private readonly DriverTrackingBoard $board,
        private readonly DriverMapWatchers $watchers,
    ) {}

    public function index(): InertiaResponse
    {
        return Inertia::render('platform/drivers/live', [
            'board' => $this->board->snapshot(),
            'channels' => [DriverLocationUpdated::PLATFORM_CHANNEL],
            'watchUrl' => route('platform.drivers.live.watch', absolute: false),
        ]);
    }

    public function watch(): Response
    {
        $this->watchers->touchPlatform();

        return response()->noContent();
    }
}
