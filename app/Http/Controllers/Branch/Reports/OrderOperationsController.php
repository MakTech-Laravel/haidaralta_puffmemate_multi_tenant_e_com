<?php

namespace App\Http\Controllers\Branch\Reports;

use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use App\Services\Order\OrderOperationsReportService;
use Inertia\Inertia;
use Inertia\Response;

class OrderOperationsController extends Controller
{
    public function index(OrderOperationsReportService $reports): Response
    {
        /** @var Branch $branch */
        $branch = app('currentBranch');

        $drivers = User::query()
            ->where('user_type', UserType::DRIVER)
            ->whereHas('driverBranchAssignments', fn ($query) => $query->where('branch_id', $branch->id))
            ->get()
            ->map(fn (User $driver): array => [
                'id' => $driver->id,
                'name' => $driver->name,
                ...$reports->driver($driver),
            ])
            ->values()
            ->all();

        return Inertia::render('branch/reports/order-operations', [
            'branch' => $branch->toPortalContext(),
            'report' => $reports->branch($branch),
            'drivers' => $drivers,
        ]);
    }
}
