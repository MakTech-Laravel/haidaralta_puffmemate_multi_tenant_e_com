<?php

use App\Http\Middleware\EnsureAnyPortalGuard;
use App\Http\Middleware\EnsurePhoneIsVerified;
use App\Http\Middleware\EnsurePortalGuard;
use App\Http\Middleware\EnsureStorefrontCustomer;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\ResolveBranchContext;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(
            at: ['10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16'],
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO,
        );

        $middleware->encryptCookies(except: ['appearance', 'sidebar_state', 'app_timezone']);

        // Platform ops stay reachable while Application Settings maintenance is on.
        $middleware->preventRequestsDuringMaintenance(except: [
            'platform',
            'platform/*',
            'up',
        ]);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'ensure.guard' => EnsurePortalGuard::class,
            'ensure.any.portal' => EnsureAnyPortalGuard::class,
            'ensure.storefront.customer' => EnsureStorefrontCustomer::class,
            'resolve.branch' => ResolveBranchContext::class,
            'permission' => PermissionMiddleware::class,
            'role' => RoleMiddleware::class,
            'precognitive' => HandlePrecognitiveRequests::class,
            'phone.verified' => EnsurePhoneIsVerified::class,
        ]);

        $middleware->redirectGuestsTo(fn () => route('login'));

        $middleware->priority([
            EnsureAnyPortalGuard::class,
            EnsurePortalGuard::class,
            ResolveBranchContext::class,
            HandleInertiaRequests::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
