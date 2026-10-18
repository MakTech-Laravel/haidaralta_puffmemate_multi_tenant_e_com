<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Services\Driver\DriverEarningsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EarningsController extends Controller
{
    public function __construct(private DriverEarningsService $earnings) {}

    public function index(Request $request): Response
    {
        $period = $this->earnings->resolvePeriod($request->query('period'));

        return Inertia::render('driver/earnings/index', [
            'earnings' => $this->earnings->summary($request->user(), $period),
            'filters' => [
                'period' => $period,
            ],
        ]);
    }
}
