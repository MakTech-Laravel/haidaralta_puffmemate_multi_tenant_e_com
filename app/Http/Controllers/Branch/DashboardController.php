<?php

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the branch dashboard.
     */
    public function index(): Response
    {
        /** @var Branch $branch */
        $branch = app('currentBranch');

        return Inertia::render('branch/dashboard', [
            'branch' => $branch->only(['id', 'name', 'slug']),
        ]);
    }
}
