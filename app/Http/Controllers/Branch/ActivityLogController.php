<?php

namespace App\Http\Controllers\Branch;

use App\DataTables\ActivityLogDataTable;
use App\Http\Controllers\Controller;
use App\Models\Branch;
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
        $branch = $this->currentBranch();

        return Inertia::render('branch/activity-logs/index', [
            'branch' => $branch->toPortalContext(),
            'table' => ActivityLogDataTable::forBranch($request, $branch),
        ]);
    }

    public function show(string $branch, int $activity): Response
    {
        $current = $this->currentBranch();
        $model = $this->activityLogs->findForBranch((int) $current->id, $activity);

        if ($model === null) {
            throw new NotFoundHttpException;
        }

        return Inertia::render('branch/activity-logs/show', [
            'branch' => $current->toPortalContext(),
            'activity' => $this->activityLogs->toDetail($model),
        ]);
    }

    private function currentBranch(): Branch
    {
        /** @var Branch $branch */
        $branch = app('currentBranch');

        return $branch;
    }
}
