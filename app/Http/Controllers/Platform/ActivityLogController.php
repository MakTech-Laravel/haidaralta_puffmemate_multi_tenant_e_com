<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Services\ActivityLog\ActivityLogService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    public function __construct(private ActivityLogService $activityLogs) {}

    /**
     * Display a paginated list of activity logs.
     */
    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->toString();
        $event = $request->string('event')->trim()->toString();

        return Inertia::render('platform/activity-logs/index', [
            'activities' => $this->activityLogs->paginateForIndex($search, $event),
            'filters' => [
                'search' => $search,
                'event' => $event,
            ],
        ]);
    }

    /**
     * Display a single activity log entry.
     */
    public function show(Activity $activity): Response
    {
        return Inertia::render('platform/activity-logs/show', [
            'activity' => $this->activityLogs->toDetail($activity),
        ]);
    }
}
