<?php

namespace App\Http\Controllers\Branch;

use App\Events\DriverLocationUpdated;
use App\Http\Controllers\Controller;
use App\Models\Branch;
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

    public function index(Branch $branch): InertiaResponse
    {
        return Inertia::render('branch/drivers/live', [
            'branch' => $branch->toPortalContext(),
            'board' => $this->board->snapshot([$branch->id]),
            'channels' => [DriverLocationUpdated::branchChannel($branch->id)],
            'watchUrl' => route('branch.drivers.live.watch', $branch, absolute: false),
        ]);
    }

    public function watch(Branch $branch): Response
    {
        $this->watchers->touchBranch($branch->id);

        return response()->noContent();
    }
}
