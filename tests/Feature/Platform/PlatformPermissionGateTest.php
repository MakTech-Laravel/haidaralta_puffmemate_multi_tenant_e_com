<?php

use App\Enums\FulfillmentStatus;
use App\Enums\HandoverVerification;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\PermissionEnum;
use App\Enums\UserType;
use App\Models\Order;
use App\Models\OrderFulfillment;
use App\Models\Payment;
use App\Models\User;
use App\Support\PlatformPermissionProbe;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
});

test('view-dashboard gates dashboard notifications realtime and fcm', function () {
    $withPermission = platformPermissionProbeUser(PermissionEnum::VIEW_DASHBOARD);
    $withoutPermission = User::factory()->create([
        'user_type' => UserType::PLATFORM,
        'email_verified_at' => now(),
        'is_active' => true,
    ]);

    $this->actingAs($withPermission, 'platform')
        ->get(route('platform.dashboard'))
        ->assertOk();

    $this->actingAs($withPermission, 'platform')
        ->get(route('platform.notifications.index'))
        ->assertOk();

    $this->actingAs($withPermission, 'platform')
        ->get(route('platform.realtime.index'))
        ->assertOk();

    $this->actingAs($withPermission, 'platform')
        ->get(route('platform.fcm.index'))
        ->assertOk();

    $this->actingAs($withoutPermission, 'platform')
        ->get(route('platform.dashboard'))
        ->assertForbidden();

    $this->actingAs($withPermission, 'platform')
        ->get(route(platformPermissionControlRoute(PermissionEnum::VIEW_DASHBOARD)))
        ->assertForbidden();
});

test('manage-branches unlocks branch list and create as a CRUD convenience permission', function () {
    expect(platformRouteUsesPermission(PermissionEnum::MANAGE_BRANCHES->value))->toBeTrue();

    $user = platformPermissionProbeUser(PermissionEnum::MANAGE_BRANCHES);

    $this->actingAs($user, 'platform')
        ->get(route('platform.branches.index'))
        ->assertOk();

    $this->actingAs($user, 'platform')
        ->get(route('platform.branches.create'))
        ->assertOk();

    $this->actingAs($user, 'platform')
        ->get(route(platformPermissionControlRoute(PermissionEnum::MANAGE_BRANCHES)))
        ->assertForbidden();
});

test('delete-branches middleware is registered and alone does not unlock edit', function () {
    expect(platformRouteUsesPermission(PermissionEnum::DELETE_BRANCHES->value))->toBeTrue();

    $destroy = Route::getRoutes()->getByName('platform.branches.destroy');
    expect($destroy)->not->toBeNull();
    expect(collect($destroy->gatherMiddleware())->contains(
        fn (mixed $middleware): bool => is_string($middleware)
            && str_contains($middleware, 'permission:'.PermissionEnum::DELETE_BRANCHES->value),
    ))->toBeTrue();

    $user = platformPermissionProbeUser(PermissionEnum::DELETE_BRANCHES);
    $branch = platformProbeBranch();

    $this->actingAs($user, 'platform')
        ->get(route('platform.branches.edit', $branch))
        ->assertForbidden();

    $this->actingAs($user, 'platform')
        ->get(route(platformPermissionControlRoute(PermissionEnum::DELETE_BRANCHES)))
        ->assertForbidden();
});

test('complete-orders-without-code alone cannot open orders index', function () {
    $user = platformPermissionProbeUser(
        PermissionEnum::COMPLETE_ORDERS_WITHOUT_CODE,
        [PermissionEnum::COMPLETE_ORDERS_WITHOUT_CODE],
    );

    $this->actingAs($user, 'platform')
        ->get(route('platform.orders.index'))
        ->assertForbidden();
});

test('complete-orders-without-code with manage-orders can deliver without code', function () {
    $user = platformPermissionProbeUser(PermissionEnum::COMPLETE_ORDERS_WITHOUT_CODE);
    $branch = platformProbeBranch();
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'email_verified_at' => now(),
    ]);
    $order = Order::factory()->create([
        'status' => OrderStatus::Arrived,
        'shipping_address_snapshot' => ['phone' => '+61410111222', 'name' => 'Ada'],
    ]);
    OrderFulfillment::factory()->forOrder($order)->create([
        'branch_id' => $branch->id,
        'driver_id' => $driver->id,
        'status' => FulfillmentStatus::Arrived,
    ]);
    Payment::factory()->create([
        'order_id' => $order->id,
        'status' => PaymentStatus::PendingCollection,
        'gateway' => 'cod',
    ]);

    $this->actingAs($user, 'platform')
        ->post(route('platform.orders.deliver-without-code', $order), [
            'method' => HandoverVerification::PhoneCall->value,
            'note' => 'Called the number on the order and the customer confirmed',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('platform.orders.show', $order));

    $this->actingAs($user, 'platform')
        ->get(route(platformPermissionControlRoute(PermissionEnum::COMPLETE_ORDERS_WITHOUT_CODE)))
        ->assertForbidden();
});

/**
 * Canonical allow + control forbid for every platform permission that has a real probe route.
 *
 * @return array<string, array{0: PermissionEnum, 1: Closure(): string}>
 */
dataset('platformPermissionCanonicalRoutes', function () {
    $branch = fn (): string => route('platform.branches.edit', platformProbeBranch());

    return [
        'manage-users' => [
            PermissionEnum::MANAGE_USERS,
            fn (): string => route('platform.customers.index'),
        ],
        'manage-branch-users' => [
            PermissionEnum::MANAGE_BRANCH_USERS,
            fn (): string => route('platform.branch-staff.index'),
        ],
        'manage-roles' => [
            PermissionEnum::MANAGE_ROLES,
            fn (): string => route('platform.permissions.index'),
        ],
        'view-branches' => [
            PermissionEnum::VIEW_BRANCHES,
            fn (): string => route('platform.branches.index'),
        ],
        'manage-branches' => [
            PermissionEnum::MANAGE_BRANCHES,
            fn (): string => route('platform.branches.index'),
        ],
        'create-branches' => [
            PermissionEnum::CREATE_BRANCHES,
            fn (): string => route('platform.branches.create'),
        ],
        'edit-branches' => [
            PermissionEnum::EDIT_BRANCHES,
            $branch,
        ],
        'view-dashboard' => [
            PermissionEnum::VIEW_DASHBOARD,
            fn (): string => route('platform.dashboard'),
        ],
        'manage-catalog' => [
            PermissionEnum::MANAGE_CATALOG,
            fn (): string => route('platform.products.index'),
        ],
        'manage-branch-inventories' => [
            PermissionEnum::MANAGE_BRANCH_INVENTORIES,
            fn (): string => route('platform.stock.manage'),
        ],
        'manage-orders' => [
            PermissionEnum::MANAGE_ORDERS,
            fn (): string => route('platform.orders.index'),
        ],
        'manage-wholesale' => [
            PermissionEnum::MANAGE_WHOLESALE,
            fn (): string => route('platform.wholesale-inquiries.index'),
        ],
        'manage-reports' => [
            PermissionEnum::MANAGE_REPORTS,
            fn (): string => route('platform.announcements.index'),
        ],
        'view-activity-logs' => [
            PermissionEnum::VIEW_ACTIVITY_LOGS,
            fn (): string => route('platform.activity-logs.index'),
        ],
        'manage-database-backups' => [
            PermissionEnum::MANAGE_DATABASE_BACKUPS,
            fn (): string => route('platform.database-backups.index'),
        ],
        'manage-application-settings' => [
            PermissionEnum::MANAGE_APPLICATION_SETTINGS,
            fn (): string => route('platform.settings.general.edit'),
        ],
        'manage-pages' => [
            PermissionEnum::MANAGE_PAGES,
            fn (): string => route('platform.pages.index'),
        ],
        'view-horizon' => [
            PermissionEnum::VIEW_HORIZON,
            fn (): string => route('platform.observability.index'),
        ],
        'view-pulse' => [
            PermissionEnum::VIEW_PULSE,
            fn (): string => route('platform.observability.index'),
        ],
        'manage-drivers' => [
            PermissionEnum::MANAGE_DRIVERS,
            fn (): string => route('platform.drivers.index'),
        ],
        'manage-shifts' => [
            PermissionEnum::MANAGE_SHIFTS,
            fn (): string => route('platform.shift-templates.index'),
        ],
        'view-transfers' => [
            PermissionEnum::VIEW_TRANSFERS,
            fn (): string => route('platform.reports.transfer-history'),
        ],
        'manage-conversations' => [
            PermissionEnum::MANAGE_CONVERSATIONS,
            fn (): string => route('platform.conversations.index'),
        ],
    ];
});

test('platform permission probe allows canonical route and forbids control route', function (
    PermissionEnum $permission,
    Closure $canonicalUrl,
) {
    $user = platformPermissionProbeUser($permission);

    $this->actingAs($user, 'platform')
        ->get($canonicalUrl())
        ->assertOk();

    $this->actingAs($user, 'platform')
        ->get(route(platformPermissionControlRoute($permission)))
        ->assertForbidden();
})->with('platformPermissionCanonicalRoutes');

test('every platform permission is covered by the gate matrix', function () {
    $covered = collect([
        PermissionEnum::VIEW_DASHBOARD,
        PermissionEnum::MANAGE_BRANCHES,
        PermissionEnum::DELETE_BRANCHES,
        PermissionEnum::COMPLETE_ORDERS_WITHOUT_CODE,
        PermissionEnum::MANAGE_USERS,
        PermissionEnum::MANAGE_BRANCH_USERS,
        PermissionEnum::MANAGE_ROLES,
        PermissionEnum::VIEW_BRANCHES,
        PermissionEnum::CREATE_BRANCHES,
        PermissionEnum::EDIT_BRANCHES,
        PermissionEnum::MANAGE_CATALOG,
        PermissionEnum::MANAGE_BRANCH_INVENTORIES,
        PermissionEnum::MANAGE_ORDERS,
        PermissionEnum::MANAGE_WHOLESALE,
        PermissionEnum::MANAGE_REPORTS,
        PermissionEnum::VIEW_ACTIVITY_LOGS,
        PermissionEnum::MANAGE_DATABASE_BACKUPS,
        PermissionEnum::MANAGE_APPLICATION_SETTINGS,
        PermissionEnum::MANAGE_PAGES,
        PermissionEnum::VIEW_HORIZON,
        PermissionEnum::VIEW_PULSE,
        PermissionEnum::MANAGE_DRIVERS,
        PermissionEnum::MANAGE_SHIFTS,
        PermissionEnum::VIEW_TRANSFERS,
        PermissionEnum::MANAGE_CONVERSATIONS,
    ])->map(fn (PermissionEnum $permission) => $permission->value)->sort()->values();

    $all = collect(PermissionEnum::forGuard('platform'))
        ->map(fn (PermissionEnum $permission) => $permission->value)
        ->sort()
        ->values();

    expect($covered->all())->toBe($all->all());
});

test('probe helper documents credentials used by the seeder', function () {
    expect(PlatformPermissionProbe::PASSWORD)->toBe('password')
        ->and(PlatformPermissionProbe::emailFor(PermissionEnum::MANAGE_ORDERS))
        ->toBe('probe+manage-orders@puffmemate.dev')
        ->and(PlatformPermissionProbe::phoneFor(PermissionEnum::MANAGE_ORDERS))
        ->toStartWith('+614709');
});
