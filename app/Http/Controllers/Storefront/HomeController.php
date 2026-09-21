<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Support\PortalAuth;
use App\Support\Storefront\DemoCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(): RedirectResponse|Response
    {
        $guard = PortalAuth::activeGuard();

        if (in_array($guard, ['platform', 'branch', 'driver'], true)) {
            $user = Auth::guard($guard)->user();
            $user->loadMissing('branch');

            return redirect(PortalAuth::dashboardPath($user));
        }

        return Inertia::render('storefront/home', [
            'featuredCategories' => DemoCatalog::featuredCategories(),
            'bestSellingProducts' => DemoCatalog::bestSellingProducts(),
            'newArrivalProducts' => DemoCatalog::newArrivalProducts(),
        ]);
    }
}
