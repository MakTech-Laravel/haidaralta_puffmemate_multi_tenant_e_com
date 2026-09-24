<?php

namespace App\Http\Middleware;

use App\Enums\RoleEnum;
use App\Models\Branch;
use App\Models\User;
use App\Services\Application\ApplicationSettingsService;
use App\Services\Application\ContactSettingsService;
use App\Services\Application\PaymentGatewaySettingsService;
use App\Support\MediaUrl;
use App\Support\PortalAuth;
use App\Support\Storefront\DemoCatalog;
use App\Support\Storefront\StorefrontCatalog;
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
            'application' => app(ApplicationSettingsService::class)->sharedPayload(),
            'contacts' => app(ContactSettingsService::class)->sharedPayload(),
            'paymentGateways' => app(PaymentGatewaySettingsService::class)->enabledMethods(),
            'auth' => $auth,
            'portal' => $portal,
            'branch' => $branch,
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'catalog' => [
                'branch_can_create_categories' => (bool) config('catalog.branch_can_create_categories'),
                'branch_can_view_categories' => (bool) config('catalog.branch_can_view_categories'),
                'show_parent_category' => (bool) config('catalog.show_parent_category'),
                'show_product_variants' => (bool) config('catalog.show_product_variants'),
                'show_bulk_import' => (bool) config('catalog.show_bulk_import'),
                'show_branch_pricing' => (bool) config('catalog.show_branch_pricing'),
                'branch_can_manage_catalog_pricing' => (bool) config('catalog.branch_can_manage_catalog_pricing'),
                'sku_prefix' => (string) config('catalog.sku_prefix', 'PMM'),
            ],
            'drivers' => [
                'single_active_branch' => (bool) config('drivers.single_active_branch'),
            ],
            'shifts' => [
                'allow_multiple_per_day' => (bool) config('shifts.allow_multiple_per_day'),
                'show_platform_administrator_shifts' => (bool) config('shifts.show_platform_administrator_shifts'),
            ],
            'storefront' => [
                'navCategories' => StorefrontCatalog::navCategories(),
                'selectedBranchId' => $request->session()->get(StorefrontCatalog::BRANCH_SESSION_KEY),
            ],
            'customerNotifications' => $portal === 'customer'
                ? array_slice(DemoCatalog::customerNotifications(), 0, 5)
                : [],
            'driverSupport' => $portal === 'driver'
                ? DemoCatalog::driverSupportContacts()
                : null,
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
                    'avatar' => MediaUrl::resolve($user->avatar, fallback: null),
                ]),
            ],
            $user->user_type->value,
            $this->resolveSharedBranch(),
        ];
    }

    /**
     * @return array{id: int, name: string, slug: string, logo_url: string|null}|null
     */
    private function resolveSharedBranch(): ?array
    {
        if (! app()->bound('currentBranch')) {
            return null;
        }

        /** @var Branch $branch */
        $branch = app('currentBranch');

        return $branch->toPortalContext();
    }
}
