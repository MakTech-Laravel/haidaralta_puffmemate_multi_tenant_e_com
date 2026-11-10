<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\Driver\DriverTrackingGate;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps a driver who should be sharing location out of the portal until they allow it again.
 *
 * Off duty, on break, and while the gate is disabled the driver is never blocked. Only the
 * location endpoint, the gate page and logout stay reachable while blocked.
 */
class EnsureDriverLocationShared
{
    private const EXEMPT_ROUTES = [
        'driver.location.store',
        'driver.location.required',
    ];

    public function __construct(private readonly DriverTrackingGate $gate) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User
            || $request->routeIs(self::EXEMPT_ROUTES)
            || ! $this->gate->isBlocked($user)) {
            return $next($request);
        }

        if ($request->expectsJson() && ! $request->hasHeader('X-Inertia')) {
            return response()->json([
                'message' => 'Location sharing is required while you are on duty.',
                'gate_url' => route('driver.location.required'),
            ], 423);
        }

        if ($request->isMethod('GET') && $request->hasSession()) {
            $request->session()->put('url.intended', $request->fullUrl());
        }

        return redirect()->route('driver.location.required');
    }
}
