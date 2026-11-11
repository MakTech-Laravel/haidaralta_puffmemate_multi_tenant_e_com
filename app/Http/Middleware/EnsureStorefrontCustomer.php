<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\Toast;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Checkout is open to everyone. Guests and staff portal users (platform / branch /
 * driver) order the guest way, through the customer resolved from the mobile number.
 * A logged-in customer must be an active customer account.
 */
class EnsureStorefrontCustomer
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('customer')->check()) {
            return $next($request);
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
