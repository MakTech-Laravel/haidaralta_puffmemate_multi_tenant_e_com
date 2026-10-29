<?php

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Models\Branch;
use App\Models\User;
use App\Support\BranchPermissionProbe;
use App\Support\PortalAuth;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Spatie\Permission\PermissionRegistrar;

/**
 * @return list<array{suffix: string, permissions: list<string>}>
 */
function branchNavDestinations(): array
{
    return [
        ['suffix' => '/dashboard', 'permissions' => ['view-branch-dashboard']],
        ['suffix' => '/staff', 'permissions' => ['manage-branch-staff']],
        ['suffix' => '/roles', 'permissions' => ['manage-branch-roles']],
        ['suffix' => '/permissions', 'permissions' => ['manage-branch-roles']],
        ['suffix' => '/shifts', 'permissions' => ['manage-branch-shifts']],
        ['suffix' => '/shift-assignments', 'permissions' => ['manage-branch-shifts']],
        ['suffix' => '/customers', 'permissions' => ['manage-branch-orders']],
        ['suffix' => '/reviews', 'permissions' => ['manage-branch-orders']],
        ['suffix' => '/drivers', 'permissions' => ['manage-branch-drivers']],
        ['suffix' => '/assign-products', 'permissions' => ['manage-branch-drivers']],
        ['suffix' => '/products', 'permissions' => ['manage-branch-products']],
        ['suffix' => '/packs-saves', 'permissions' => ['manage-branch-products']],
        ['suffix' => '/stock/manage', 'permissions' => ['manage-branch-products']],
        ['suffix' => '/orders', 'permissions' => ['manage-branch-orders']],
        ['suffix' => '/stock-transfers', 'permissions' => ['manage-branch-orders']],
        ['suffix' => '/areas', 'permissions' => ['manage-branch-settings']],
        ['suffix' => '/conversations', 'permissions' => ['manage-branch-conversations']],
        ['suffix' => '/driver-chats', 'permissions' => ['manage-branch-driver-chat']],
        ['suffix' => '/reports/order-reports', 'permissions' => ['manage-branch-orders']],
        ['suffix' => '/reports/order-assign-history', 'permissions' => ['manage-branch-orders']],
        ['suffix' => '/reports/stock-transactions', 'permissions' => ['manage-branch-products']],
        ['suffix' => '/reports/product-assign-transactions', 'permissions' => ['manage-branch-drivers']],
        ['suffix' => '/settings/general', 'permissions' => ['manage-branch-settings']],
        ['suffix' => '/notifications', 'permissions' => ['view-branch-dashboard']],
        ['suffix' => '/activity-logs', 'permissions' => ['view-branch-activity-logs']],
    ];
}

function branchUserMayAccessNav(User $user, array $requiredPermissions): bool
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

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);
    $this->branch = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
});

dataset('branchRoleEnumAccounts', [
    'super-admin' => [fn () => User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail()],
    'admin' => [fn () => User::query()->where('email', UserSeeder::BRANCH_1_ADMIN)->firstOrFail()],
    'staff' => [fn () => User::query()->where('email', UserSeeder::BRANCH_1_STAFF)->firstOrFail()],
]);

test('branch RoleEnum accounts get expected allow or forbid on every nav destination', function (Closure $resolveUser) {
    $user = $resolveUser();
    $slug = $this->branch->slug;

    app(PermissionRegistrar::class)->setPermissionsTeamId($this->branch->id);

    foreach (branchNavDestinations() as $destination) {
        $path = '/branch/'.$slug.$destination['suffix'];
        $response = $this->actingAs($user, 'branch')->get($path);

        if (branchUserMayAccessNav($user, $destination['permissions'])) {
            $response->assertOk("Expected 200 for {$user->email} on {$path}");
        } else {
            $response->assertForbidden("Expected 403 for {$user->email} on {$path}");
        }
    }
})->with('branchRoleEnumAccounts');

test('each branch permission probe allows matching nav destinations and forbids control', function (PermissionEnum $permission) {
    $branch = $this->branch;
    $user = BranchPermissionProbe::ensureUser($branch, $permission);
    $permissionNames = collect(BranchPermissionProbe::permissionNamesFor($permission));
    $slug = $branch->slug;
    $matched = false;

    app(PermissionRegistrar::class)->setPermissionsTeamId($branch->id);

    foreach (branchNavDestinations() as $destination) {
        $shouldAllow = collect($destination['permissions'])
            ->contains(fn (string $required) => $permissionNames->contains($required));

        $path = '/branch/'.$slug.$destination['suffix'];
        $response = $this->actingAs($user, 'branch')->get($path);

        if ($shouldAllow) {
            $matched = true;
            $response->assertOk("Probe {$permission->value} should open {$path}");
        } else {
            $response->assertForbidden("Probe {$permission->value} should be forbidden on {$path}");
        }
    }

    expect($matched)->toBeTrue("Probe {$permission->value} should match at least one nav destination");

    $control = $permission === PermissionEnum::MANAGE_BRANCH_ROLES
        ? '/branch/'.$slug.'/customers'
        : '/branch/'.$slug.'/permissions';

    $this->actingAs($user, 'branch')->get($control)->assertForbidden();
})->with(PermissionEnum::forGuard('branch'));

test('branch catalog-only probe lands on products after login instead of forbidden dashboard', function () {
    $user = BranchPermissionProbe::ensureUser($this->branch, PermissionEnum::MANAGE_BRANCH_PRODUCTS);

    expect(PortalAuth::dashboardPath($user))
        ->toBe('/branch/'.BranchSeeder::SYDNEY_CBD.'/products');

    $this->post(route('login.store'), [
        'phone' => BranchPermissionProbe::phoneFor(PermissionEnum::MANAGE_BRANCH_PRODUCTS),
        'password' => BranchPermissionProbe::PASSWORD,
    ])->assertRedirect('/branch/'.BranchSeeder::SYDNEY_CBD.'/products');
});
