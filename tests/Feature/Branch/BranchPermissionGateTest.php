<?php

use App\Enums\PermissionEnum;
use App\Enums\UserType;
use App\Models\User;
use App\Support\BranchPermissionProbe;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->branch = branchProbeBranch();
});

test('view-branch-dashboard gates dashboard and notifications', function () {
    $withPermission = branchPermissionProbeUser($this->branch, PermissionEnum::VIEW_BRANCH_DASHBOARD);
    $withoutPermission = User::factory()->create([
        'user_type' => UserType::BRANCH,
        'branch_id' => $this->branch->id,
        'email_verified_at' => now(),
        'is_active' => true,
    ]);

    $this->actingAs($withPermission, 'branch')
        ->get(route('branch.dashboard', $this->branch))
        ->assertOk();

    $this->actingAs($withPermission, 'branch')
        ->get(route('branch.notifications.index', $this->branch))
        ->assertOk();

    $this->actingAs($withoutPermission, 'branch')
        ->get(route('branch.dashboard', $this->branch))
        ->assertForbidden();

    $this->actingAs($withPermission, 'branch')
        ->get(route(branchPermissionControlRouteName(PermissionEnum::VIEW_BRANCH_DASHBOARD), $this->branch))
        ->assertForbidden();
});

test('complete-branch-orders-without-code alone cannot open orders index', function () {
    $user = branchPermissionProbeUser(
        $this->branch,
        PermissionEnum::COMPLETE_BRANCH_ORDERS_WITHOUT_CODE,
        [PermissionEnum::COMPLETE_BRANCH_ORDERS_WITHOUT_CODE],
    );

    $this->actingAs($user, 'branch')
        ->get(route('branch.orders.index', $this->branch))
        ->assertForbidden();
});

/**
 * @return array<string, array{0: PermissionEnum, 1: Closure(): string}>
 */
dataset('branchPermissionCanonicalRoutes', function () {
    return [
        'view-branch-dashboard' => [
            PermissionEnum::VIEW_BRANCH_DASHBOARD,
            fn (): string => route('branch.dashboard', branchProbeBranch()),
        ],
        'manage-branch-products' => [
            PermissionEnum::MANAGE_BRANCH_PRODUCTS,
            fn (): string => route('branch.products.index', branchProbeBranch()),
        ],
        'manage-branch-orders' => [
            PermissionEnum::MANAGE_BRANCH_ORDERS,
            fn (): string => route('branch.orders.index', branchProbeBranch()),
        ],
        'manage-branch-staff' => [
            PermissionEnum::MANAGE_BRANCH_STAFF,
            fn (): string => route('branch.staff.index', branchProbeBranch()),
        ],
        'manage-branch-roles' => [
            PermissionEnum::MANAGE_BRANCH_ROLES,
            fn (): string => route('branch.permissions.index', branchProbeBranch()),
        ],
        'view-branch-activity-logs' => [
            PermissionEnum::VIEW_BRANCH_ACTIVITY_LOGS,
            fn (): string => route('branch.activity-logs.index', branchProbeBranch()),
        ],
        'manage-branch-drivers' => [
            PermissionEnum::MANAGE_BRANCH_DRIVERS,
            fn (): string => route('branch.drivers.index', branchProbeBranch()),
        ],
        'manage-branch-shifts' => [
            PermissionEnum::MANAGE_BRANCH_SHIFTS,
            fn (): string => route('branch.shift-templates.index', branchProbeBranch()),
        ],
        'manage-branch-conversations' => [
            PermissionEnum::MANAGE_BRANCH_CONVERSATIONS,
            fn (): string => route('branch.conversations.index', branchProbeBranch()),
        ],
        'manage-branch-driver-chat' => [
            PermissionEnum::MANAGE_BRANCH_DRIVER_CHAT,
            fn (): string => route('branch.driver-chats.index', branchProbeBranch()),
        ],
        'manage-branch-platform-chat' => [
            PermissionEnum::MANAGE_BRANCH_PLATFORM_CHAT,
            fn (): string => route('branch.platform-chats.index', branchProbeBranch()),
        ],
        'manage-branch-settings' => [
            PermissionEnum::MANAGE_BRANCH_SETTINGS,
            fn (): string => route('branch.settings.general.edit', branchProbeBranch()),
        ],
        'complete-branch-orders-without-code' => [
            PermissionEnum::COMPLETE_BRANCH_ORDERS_WITHOUT_CODE,
            fn (): string => route('branch.orders.index', branchProbeBranch()),
        ],
    ];
});

test('branch permission probe allows canonical route and forbids control route', function (
    PermissionEnum $permission,
    Closure $canonicalUrl,
) {
    $user = branchPermissionProbeUser(branchProbeBranch(), $permission);

    $this->actingAs($user, 'branch')
        ->get($canonicalUrl())
        ->assertOk();

    $this->actingAs($user, 'branch')
        ->get(route(branchPermissionControlRouteName($permission), branchProbeBranch()))
        ->assertForbidden();
})->with('branchPermissionCanonicalRoutes');

test('every branch permission is covered by the gate matrix', function () {
    $covered = collect([
        PermissionEnum::VIEW_BRANCH_DASHBOARD,
        PermissionEnum::MANAGE_BRANCH_PRODUCTS,
        PermissionEnum::MANAGE_BRANCH_ORDERS,
        PermissionEnum::COMPLETE_BRANCH_ORDERS_WITHOUT_CODE,
        PermissionEnum::MANAGE_BRANCH_STAFF,
        PermissionEnum::MANAGE_BRANCH_ROLES,
        PermissionEnum::VIEW_BRANCH_ACTIVITY_LOGS,
        PermissionEnum::MANAGE_BRANCH_DRIVERS,
        PermissionEnum::MANAGE_BRANCH_SHIFTS,
        PermissionEnum::MANAGE_BRANCH_CONVERSATIONS,
        PermissionEnum::MANAGE_BRANCH_DRIVER_CHAT,
        PermissionEnum::MANAGE_BRANCH_PLATFORM_CHAT,
        PermissionEnum::MANAGE_BRANCH_SETTINGS,
    ])->map(fn (PermissionEnum $permission) => $permission->value)->sort()->values();

    $all = collect(PermissionEnum::forGuard('branch'))
        ->map(fn (PermissionEnum $permission) => $permission->value)
        ->sort()
        ->values();

    expect($covered->all())->toBe($all->all());
});

test('branch probe helper documents credentials used by the seeder', function () {
    expect(BranchPermissionProbe::PASSWORD)->toBe('password')
        ->and(BranchPermissionProbe::emailFor(PermissionEnum::MANAGE_BRANCH_ORDERS))
        ->toBe('bprobe+manage-branch-orders@puffmemate.dev')
        ->and(BranchPermissionProbe::phoneFor(PermissionEnum::MANAGE_BRANCH_ORDERS))
        ->toStartWith('+614708');
});
