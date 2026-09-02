<?php

namespace App\Http\Middleware;

use App\Enums\RoleEnum;
use App\Models\Branch;
use App\Models\User;
use App\Support\PortalAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$auth, $portal, $branch] = $this->resolveSharedAuth();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => $auth,
            'portal' => $portal,
            'branch' => $branch,
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    /**
     * @return array{0: array{user: array<string, mixed>|null}, 1: string|null, 2: array{id: int, name: string, slug: string}|null}
     */
    private function resolveSharedAuth(): array
    {
        $guard = PortalAuth::activeGuard();

        if ($guard === null) {
            return [
                ['user' => null],
                null,
                null,
            ];
        }

        /** @var User $user */
        $user = Auth::guard($guard)->user();

        return [
            [
                'user' => array_merge($user->toArray(), [
                    'roles' => $user->getRoleNames()->values()->all(),
                    'permissions' => $user->getAllPermissions()->pluck('name')->values()->all(),
                    'is_super_admin' => $user->hasRole(RoleEnum::SUPER_ADMIN->value),
                ]),
            ],
            $user->user_type->value,
            $this->resolveSharedBranch(),
        ];
    }

    /**
     * @return array{id: int, name: string, slug: string}|null
     */
    private function resolveSharedBranch(): ?array
    {
        if (! app()->bound('currentBranch')) {
            return null;
        }

        /** @var Branch $branch */
        $branch = app('currentBranch');

        return $branch->only(['id', 'name', 'slug']);
    }
}
