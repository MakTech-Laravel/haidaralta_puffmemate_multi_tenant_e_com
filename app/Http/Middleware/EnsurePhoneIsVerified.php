<?php

namespace App\Http\Middleware;

use App\Support\PortalAuth;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePhoneIsVerified
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user() ?? PortalAuth::user();

        if ($user === null) {
            return redirect()->route('login');
        }

        if ($user->phone_verified_at === null) {
            return redirect()->route('phone.verification.notice');
        }

        return $next($request);
    }
}
