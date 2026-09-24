<?php

namespace App\Http\Middleware;

use App\Enums\UserType;
use App\Models\Branch;
use App\Models\User;
use App\Support\PortalAuth;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

class EnsureAnyPortalGuard
{
    /**
     * Ensure the request is authenticated on any portal guard, then set it as default.
     *
     * Shared routes (settings) must not use plain `auth` (default `web`) — portal
     * users log in on platform/branch/driver/customer guards only.
     *
     * Branch users also get currentBranch + Spatie team scope so settings keep
     * the branch portal chrome and permission-gated nav.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $guard = PortalAuth::activeGuard();

        if ($guard === null) {
            return redirect()->guest(route('login'));
        }

        Auth::shouldUse($guard);

        /** @var User $user */
        $user = Auth::guard($guard)->user();

        if (! $user->is_active) {
            Auth::guard($guard)->logout();

            return redirect()
                ->route('login')
                ->withErrors(['phone' => __('auth.failed')]);
        }

        if ($user->user_type === UserType::BRANCH && $user->branch_id !== null) {
            $this->bindBranchContext($user);
        }

        return $next($request);
    }

    private function bindBranchContext(User $user): void
    {
        if (app()->bound('currentBranch')) {
            return;
        }

        $branch = Branch::query()
            ->whereKey($user->branch_id)
            ->where('status', true)
            ->first();

        if ($branch === null) {
            return;
        }

        app(PermissionRegistrar::class)->setPermissionsTeamId($branch->id);
        app()->instance('currentBranch', $branch);
    }
}
