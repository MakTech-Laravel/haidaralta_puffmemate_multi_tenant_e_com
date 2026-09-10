<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Support\OpsDashboardAccess;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ObservabilityController extends Controller
{
    /**
     * Platform hub linking to Horizon and Pulse dashboards.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        abort_unless(OpsDashboardAccess::allowsObservabilityHub($user), 403);

        return Inertia::render('platform/observability/index', [
            'canViewHorizon' => OpsDashboardAccess::allowsHorizon($user),
            'canViewPulse' => OpsDashboardAccess::allowsPulse($user),
            'horizonPath' => '/'.trim((string) config('horizon.path', 'horizon'), '/'),
            'pulsePath' => '/'.trim((string) config('pulse.path', 'pulse'), '/'),
            'queueConnection' => (string) config('queue.default'),
            'horizonReady' => config('queue.default') === 'redis',
        ]);
    }
}
