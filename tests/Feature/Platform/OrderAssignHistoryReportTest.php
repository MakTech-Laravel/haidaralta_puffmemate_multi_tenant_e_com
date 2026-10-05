<?php

use App\Enums\DriverAssignmentStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\DriverBranchAssignment;
use App\Models\Order;
use App\Models\OrderDriverAssignmentHistory;
use App\Models\OrderFulfillment;
use App\Models\Payment;
use App\Models\User;
use App\Support\PlatformTeamResolver;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);

    app(PermissionRegistrar::class)->setPermissionsTeamId(PlatformTeamResolver::PLATFORM_TEAM_ID);

    $this->admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $this->branch = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $this->driver = User::query()->where('email', UserSeeder::DRIVER)->firstOrFail();
    $this->staff = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
});

function platformOrderFulfillment(Branch $branch, array $overrides = []): OrderFulfillment
{
    $order = Order::factory()->create([
        'shipping_address_snapshot' => [
            'name' => 'Ada Lovelace',
            'phone' => '+61410111222',
            'address' => '1 Test Street',
            'city' => 'Sydney',
        ],
    ]);

    $fulfillment = OrderFulfillment::factory()->forOrder($order)->create(array_merge([
        'branch_id' => $branch->id,
        'status' => FulfillmentStatus::Ready,
    ], $overrides));

    Payment::factory()->create(['order_id' => $order->id]);

    return $fulfillment;
}

test('guests are redirected from platform order assign history', function () {
    $this->get(route('platform.reports.order-assign-history'))
        ->assertRedirect(route('login'));
});

test('platform admin can view order assign history with default today scope', function () {
    $fulfillment = platformOrderFulfillment($this->branch);

    $today = OrderDriverAssignmentHistory::query()->create([
        'order_id' => $fulfillment->order_id,
        'fulfillment_id' => $fulfillment->id,
        'from_driver_id' => null,
        'to_driver_id' => $this->driver->id,
        'reason' => null,
        'changed_by' => $this->admin->id,
    ]);

    $yesterday = OrderDriverAssignmentHistory::query()->create([
        'order_id' => $fulfillment->order_id,
        'fulfillment_id' => $fulfillment->id,
        'from_driver_id' => null,
        'to_driver_id' => $this->driver->id,
        'reason' => 'Old',
        'changed_by' => $this->admin->id,
    ]);
    $yesterday->forceFill([
        'created_at' => now()->subDay(),
        'updated_at' => now()->subDay(),
    ])->saveQuietly();

    $this->actingAs($this->admin, 'platform')
        ->get(route('platform.reports.order-assign-history'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('platform/reports/order-assign-history')
            ->where('table.filters.date', now()->toDateString())
            ->has('table.data', 1)
            ->where('table.data.0.id', $today->id)
            ->where('table.data.0.changed_by_name', 'System')
            ->where('table.data.0.changed_by_url', null));
});

test('platform assign history shows branch staff name and staff url', function () {
    $fulfillment = platformOrderFulfillment($this->branch);

    OrderDriverAssignmentHistory::query()->create([
        'order_id' => $fulfillment->order_id,
        'fulfillment_id' => $fulfillment->id,
        'from_driver_id' => null,
        'to_driver_id' => $this->driver->id,
        'reason' => null,
        'changed_by' => $this->staff->id,
    ]);

    $this->actingAs($this->admin, 'platform')
        ->get(route('platform.reports.order-assign-history', [
            'filter' => [
                'order_id' => $fulfillment->fulfillment_number,
            ],
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('platform/reports/order-assign-history')
            ->has('table.data', 1)
            ->where('table.data.0.changed_by_name', $this->staff->name)
            ->where('table.data.0.changed_by_url', route('platform.branch-staff.show', $this->staff)));
});

test('platform admin can assign a driver and history records platform actor as System in report', function () {
    $fulfillment = platformOrderFulfillment($this->branch, [
        'status' => FulfillmentStatus::Ready,
        'driver_id' => null,
    ]);

    $this->actingAs($this->admin, 'platform')
        ->post(route('platform.orders.assign-driver', $fulfillment->order), [
            'driver_id' => $this->driver->id,
        ])
        ->assertRedirect(route('platform.orders.show', $fulfillment->order));

    $history = OrderDriverAssignmentHistory::query()
        ->where('fulfillment_id', $fulfillment->id)
        ->latest('id')
        ->first();

    expect($history)->not->toBeNull()
        ->and($history->changed_by)->toBe($this->admin->id)
        ->and($history->to_driver_id)->toBe($this->driver->id);

    $this->actingAs($this->admin, 'platform')
        ->get(route('platform.reports.order-assign-history', [
            'filter' => [
                'order_id' => $fulfillment->fulfillment_number,
            ],
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('table.data', 1)
            ->where('table.data.0.changed_by_name', 'System')
            ->where('table.data.0.changed_by_url', null));
});

test('platform reassign requires a reason', function () {
    $replacement = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'branch_id' => null,
        'is_active' => true,
    ]);
    DriverBranchAssignment::factory()->create([
        'driver_id' => $replacement->id,
        'branch_id' => $this->branch->id,
        'status' => DriverAssignmentStatus::Active,
    ]);

    $fulfillment = platformOrderFulfillment($this->branch, [
        'status' => FulfillmentStatus::Assigned,
        'driver_id' => $this->driver->id,
    ]);

    $this->actingAs($this->admin, 'platform')
        ->post(route('platform.orders.assign-driver', $fulfillment->order), [
            'driver_id' => $replacement->id,
        ])
        ->assertSessionHasErrors('reason');
});

test('guests cannot export platform order assign history', function () {
    $this->get(route('platform.reports.order-assign-history.export', [
        'format' => 'csv',
        'scope' => 'all',
    ]))->assertRedirect(route('login'));
});

test('platform admin can export order assign history as csv', function () {
    $this->travelTo('2026-09-18 12:00:00');

    $fulfillment = platformOrderFulfillment($this->branch, [
        'fulfillment_number' => 'PLT-EXPORT-001',
    ]);

    OrderDriverAssignmentHistory::query()->create([
        'order_id' => $fulfillment->order_id,
        'fulfillment_id' => $fulfillment->id,
        'from_driver_id' => null,
        'to_driver_id' => $this->driver->id,
        'reason' => null,
        'changed_by' => $this->admin->id,
    ]);

    $response = $this->actingAs($this->admin, 'platform')
        ->get(route('platform.reports.order-assign-history.export', [
            'format' => 'csv',
            'scope' => 'all',
        ]));

    $response->assertOk()->assertDownload('order-assign-history-all-2026-09-18.csv');

    expect(file_get_contents($response->baseResponse->getFile()->getPathname()))
        ->toContain('PLT-EXPORT-001')
        ->toContain('System')
        ->toContain('Branch');
});
