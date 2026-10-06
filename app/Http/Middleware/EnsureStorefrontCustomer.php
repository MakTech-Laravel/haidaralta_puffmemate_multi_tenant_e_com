<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\PortalAuth;
use App\Support\Toast;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Checkout is customer-only. Staff portals (platform / branch / driver) are
 * redirected to their own dashboard instead of the storefront place-order flow.
 */
class EnsureStorefrontCustomer
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $activeGuard = PortalAuth::activeGuard();

        if (in_array($activeGuard, ['platform', 'branch', 'driver'], true)) {
            /** @var User $staff */
            $staff = Auth::guard($activeGuard)->user();

            Toast::error('Checkout is only available for customer accounts.');

            return redirect(PortalAuth::dashboardPath($staff));
        }

        if (! Auth::guard('customer')->check()) {
            return redirect()->guest(route('login'));
        }

        Auth::shouldUse('customer');

        /** @var User $customer */
        $customer = Auth::guard('customer')->user();

        if (! $customer->isCustomer() || ! $customer->is_active) {
            Auth::guard('customer')->logout();

            Toast::error('Checkout is only available for customer accounts.');

            return redirect()->guest(route('login'));
        }

        return $next($request);
    }
}
