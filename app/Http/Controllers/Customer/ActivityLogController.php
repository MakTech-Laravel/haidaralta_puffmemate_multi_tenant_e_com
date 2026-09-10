<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Services\ActivityLog\ActivityLogService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ActivityLogController extends Controller
{
    public function __construct(private ActivityLogService $activityLogs) {}

    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->toString();
        $event = $request->string('event')->trim()->toString();
        $userId = (int) $request->user()->id;

        return Inertia::render('customer/activity/index', [
            'activities' => $this->activityLogs->paginateForCauser($userId, $search, $event),
            'filters' => [
                'search' => $search,
                'event' => $event,
            ],
        ]);
    }

    public function show(Request $request, int $activity): Response
    {
        $model = $this->activityLogs->findForCauser((int) $request->user()->id, $activity);

        if ($model === null) {
            throw new NotFoundHttpException;
        }

        return Inertia::render('customer/activity/show', [
            'activity' => $this->activityLogs->toDetail($model),
        ]);
    }
}
