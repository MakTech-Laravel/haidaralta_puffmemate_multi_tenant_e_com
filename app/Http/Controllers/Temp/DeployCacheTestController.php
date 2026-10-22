<?php

namespace App\Http\Controllers\Temp;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * TEMP — Coolify BuildKit cache smoke test. Delete this class + page + route after verifying.
 */
class DeployCacheTestController extends Controller
{
    private const SESSION_KEY = 'temp_deploy_cache_test_pings';

    public function show(Request $request): Response
    {
        return Inertia::render('temp/deploy-cache-test', [
            'builtAt' => now()->toIso8601String(),
            'appEnv' => (string) config('app.env'),
            'phpVersion' => PHP_VERSION,
            'pingCount' => (int) $request->session()->get(self::SESSION_KEY, 0),
            'hint' => 'If Coolify log shows runtime-base CACHED, PHP was not recompiled.',
        ]);
    }

    public function ping(Request $request): RedirectResponse
    {
        $count = (int) $request->session()->get(self::SESSION_KEY, 0) + 1;
        $request->session()->put(self::SESSION_KEY, $count);

        return redirect()->route('temp.deploy-cache-test');
    }
}
