<?php

namespace App\Http\Middleware;

use App\Models\Branch;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

class ResolveBranchContext
{
    /**
     * Resolve the branch from the route slug and scope Spatie permissions to it.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $slug = $request->route('branch');

        if (! is_string($slug)) {
            abort(404);
        }

        $branch = Branch::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->first();

        if ($branch === null) {
            abort(404);
        }

        /** @var User $user */
        $user = Auth::guard('branch')->user();

        if ($user->branch_id !== $branch->id) {
            abort(403, 'You do not have access to this branch.');
        }

        app(PermissionRegistrar::class)->setPermissionsTeamId($branch->id);
        app()->instance('currentBranch', $branch);

        return $next($request);
    }
}
