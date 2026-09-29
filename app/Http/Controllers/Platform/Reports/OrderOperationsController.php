<?php

namespace App\Http\Controllers\Platform\Reports;

use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Order\OrderOperationsReportService;
use Inertia\Inertia;
use Inertia\Response;

class OrderOperationsController extends Controller
{
    public function index(OrderOperationsReportService $reports): Response
    {
        $drivers = User::query()
            ->where('user_type', UserType::DRIVER)
            ->get()
            ->map(fn (User $driver): array => [
                'id' => $driver->id,
                'name' => $driver->name,
                ...$reports->driver($driver),
            ])
            ->values()
            ->all();

        return Inertia::render('platform/reports/order-operations', [
            'report' => $reports->platform(),
            'drivers' => $drivers,
        ]);
    }
}
