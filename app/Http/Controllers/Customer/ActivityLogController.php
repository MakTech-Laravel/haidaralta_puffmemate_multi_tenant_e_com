<?php

namespace App\Http\Controllers\Customer;

use App\DataTables\ActivityLogDataTable;
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
        return Inertia::render('customer/activity/index', [
            'table' => ActivityLogDataTable::forCauser($request, $request->user()),
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
