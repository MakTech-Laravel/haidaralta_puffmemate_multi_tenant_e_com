<?php

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Enums\UserType;
use App\Models\User;
use App\Support\PlatformPermissionProbe;
use App\Support\PlatformTeamResolver;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Spatie\Permission\PermissionRegistrar;

/**
 * Canonical platform sidebar destinations (real hrefs from platform/nav.ts).
 * Permissions match leaf `permissions` (canAny). Parent-group reachability is
 * asserted separately for the payment-methods / manage-catalog parent fix.
 *
 * @return list<array{path: string, permissions: list<string>}>
 */
function platformNavDestinations(): array
{
    return [
        ['path' => '/platform/dashboard', 'permissions' => ['view-dashboard']],
        ['path' => '/platform/admins', 'permissions' => ['manage-users']],
        ['path' => '/platform/roles', 'permissions' => ['manage-roles']],
        ['path' => '/platform/permissions', 'permissions' => ['manage-roles']],
        ['path' => '/platform/customers', 'permissions' => ['manage-users']],
        ['path' => '/platform/reviews', 'permissions' => ['manage-users']],
        ['path' => '/platform/wholesale-inquiries', 'permissions' => ['manage-wholesale']],
        ['path' => '/platform/contact-inquiries', 'permissions' => ['manage-users']],
        ['path' => '/platform/drivers', 'permissions' => ['manage-drivers']],
        ['path' => '/platform/assign-products', 'permissions' => ['manage-drivers']],
        ['path' => '/platform/branches', 'permissions' => ['view-branches', 'manage-branches']],
        ['path' => '/platform/branch-areas', 'permissions' => ['view-branches', 'manage-branches']],
        ['path' => '/platform/branch-staff', 'permissions' => ['manage-branch-users']],
        ['path' => '/platform/branch-shifts', 'permissions' => ['manage-shifts']],
        ['path' => '/platform/branch-shift-assignments', 'permissions' => ['manage-shifts']],
        ['path' => '/platform/stock/manage', 'permissions' => ['manage-branch-inventories']],
        ['path' => '/platform/categories', 'permissions' => ['manage-catalog']],
        ['path' => '/platform/products', 'permissions' => ['manage-catalog']],
        ['path' => '/platform/packs-saves', 'permissions' => ['manage-catalog']],
        ['path' => '/platform/price-ranges', 'permissions' => ['manage-catalog']],
        ['path' => '/platform/delivery-settings', 'permissions' => ['manage-catalog']],
        ['path' => '/platform/catalog/pricing/manage', 'permissions' => ['manage-catalog']],
        ['path' => '/platform/orders', 'permissions' => ['manage-orders']],
        ['path' => '/platform/stock-transfers', 'permissions' => ['manage-orders']],
        ['path' => '/platform/conversations', 'permissions' => ['manage-conversations']],
        ['path' => '/platform/pages', 'permissions' => ['manage-pages']],
        ['path' => '/platform/home-banners', 'permissions' => ['manage-pages']],
        ['path' => '/platform/coupons', 'permissions' => ['manage-catalog']],
        ['path' => '/platform/announcements', 'permissions' => ['manage-reports']],
        ['path' => '/platform/reports/order-reports', 'permissions' => ['manage-reports']],
        ['path' => '/platform/reports/order-assign-history', 'permissions' => ['manage-reports']],
        ['path' => '/platform/reports/stock-transactions', 'permissions' => ['manage-branch-inventories']],
        ['path' => '/platform/reports/transfer-history', 'permissions' => ['view-transfers']],
        ['path' => '/platform/reports/product-assign-transactions', 'permissions' => ['manage-drivers']],
        ['path' => '/platform/settings/general', 'permissions' => ['manage-application-settings']],
        ['path' => '/platform/settings/contacts', 'permissions' => ['manage-application-settings']],
        ['path' => '/platform/notifications', 'permissions' => ['view-dashboard']],
        ['path' => '/platform/settings/payment-gateway', 'permissions' => ['manage-application-settings']],
        ['path' => '/platform/payment-methods', 'permissions' => ['manage-catalog']],
        ['path' => '/platform/settings/smtp', 'permissions' => ['manage-application-settings']],
        ['path' => '/platform/database-backups', 'permissions' => ['manage-database-backups']],
        ['path' => '/platform/activity-logs', 'permissions' => ['view-activity-logs']],
        ['path' => '/platform/observability', 'permissions' => ['view-horizon', 'view-pulse']],
        ['path' => '/platform/realtime', 'permissions' => ['view-dashboard']],
        ['path' => '/platform/fcm', 'permissions' => ['view-dashboard']],
    ];
}

function userMayAccessNav(User $user, array $requiredPermissions): bool
{
    if ($user->hasRole(RoleEnum::SUPER_ADMIN->value)) {
        return true;
    }

    foreach ($requiredPermissions as $permission) {
        if ($user->can($permission)) {
            return true;
        }
    }

    return false;
}

function platformStaffQaUser(): User
{
    app(PermissionRegistrar::class)->setPermissionsTeamId(PlatformTeamResolver::PLATFORM_TEAM_ID);

    $user = User::query()->updateOrCreate(
        ['email' => 'platform.staff.qa@puffmemate.dev'],
        [
            'name' => 'Platform Staff QA',
            'phone' => '+61470999999',
            'password' => UserSeeder::DEFAULT_PASSWORD,
            'user_type' => UserType::PLATFORM,
            'email_verified_at' => now(),
            'is_active' => true,
        ],
    );
    $user->syncRoles([RoleEnum::STAFF->value]);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return $user->fresh();
}

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);
});

dataset('platformRoleEnumAccounts', [
    'super-admin' => [fn () => User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail()],
    'admin' => [fn () => User::query()->where('email', UserSeeder::PLATFORM_ADMIN)->firstOrFail()],
    'manager' => [fn () => User::query()->where('email', UserSeeder::PLATFORM_MANAGER)->firstOrFail()],
    'staff' => [fn () => platformStaffQaUser()],
]);

test('platform RoleEnum accounts get expected allow or forbid on every nav destination', function (Closure $resolveUser) {
    $user = $resolveUser();

    foreach (platformNavDestinations() as $destination) {
        $response = $this->actingAs($user, 'platform')->get($destination['path']);

        if (userMayAccessNav($user, $destination['permissions'])) {
            $response->assertOk("Expected 200 for {$user->email} on {$destination['path']}");
        } else {
            $response->assertForbidden("Expected 403 for {$user->email} on {$destination['path']}");
        }
    }
})->with('platformRoleEnumAccounts');

test('each platform permission probe allows matching nav destinations and forbids control', function (PermissionEnum $permission) {
    $user = PlatformPermissionProbe::ensureUser($permission);
    $permissionNames = collect(PlatformPermissionProbe::permissionNamesFor($permission));

    $matched = false;

    foreach (platformNavDestinations() as $destination) {
        $shouldAllow = collect($destination['permissions'])
            ->contains(fn (string $required) => $permissionNames->contains($required));

        $response = $this->actingAs($user, 'platform')->get($destination['path']);

        if ($shouldAllow) {
            $matched = true;
            $response->assertOk("Probe {$permission->value} should open {$destination['path']}");
        } else {
            $response->assertForbidden("Probe {$permission->value} should be forbidden on {$destination['path']}");
        }
    }

    // Branch CRUD leaves without a dedicated sidebar href (create/edit/delete).
    $branchCrudWithoutNav = [
        PermissionEnum::CREATE_BRANCHES,
        PermissionEnum::EDIT_BRANCHES,
        PermissionEnum::DELETE_BRANCHES,
    ];

    if (in_array($permission, $branchCrudWithoutNav, true)) {
        expect(platformRouteUsesPermission($permission->value))->toBeTrue();
    } else {
        expect($matched)->toBeTrue("Probe {$permission->value} should match at least one nav destination");
    }

    $control = $permission === PermissionEnum::MANAGE_ROLES
        ? '/platform/customers'
        : '/platform/permissions';

    $this->actingAs($user, 'platform')->get($control)->assertForbidden();
})->with(PermissionEnum::forGuard('platform'));
