<?php

use App\Enums\DriverAssignmentStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\RoleEnum;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\DriverBranchAssignment;
use App\Models\Order;
use App\Models\OrderDriverAssignmentHistory;
use App\Models\OrderFulfillment;
use App\Models\Payment;
use App\Models\User;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);

    $this->staff = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $this->branch = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $this->driver = User::query()->where('email', UserSeeder::DRIVER)->firstOrFail();
});

function orderAssignHistoryFulfillment(Branch $branch, array $overrides = []): OrderFulfillment
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

test('guests are redirected from order assign history', function () {
    $this->get(route('branch.reports.order-assign-history', $this->branch))
        ->assertRedirect(route('login'));
});

test('staff without manage-branch-orders cannot open order assign history', function () {
    $staff = User::factory()->create([
        'user_type' => UserType::BRANCH,
        'branch_id' => $this->branch->id,
        'email_verified_at' => now(),
        'is_active' => true,
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId($this->branch->id);
    $staff->assignRole(RoleEnum::STAFF->value);

    $this->actingAs($staff, 'branch')
        ->get(route('branch.reports.order-assign-history', $this->branch))
        ->assertForbidden();
});

test('branch staff can view order assign history with default today scope', function () {
    $fulfillment = orderAssignHistoryFulfillment($this->branch);

    $today = OrderDriverAssignmentHistory::query()->create([
        'order_id' => $fulfillment->order_id,
        'fulfillment_id' => $fulfillment->id,
        'from_driver_id' => null,
        'to_driver_id' => $this->driver->id,
        'reason' => null,
        'changed_by' => $this->staff->id,
    ]);

    $yesterday = OrderDriverAssignmentHistory::query()->create([
        'order_id' => $fulfillment->order_id,
        'fulfillment_id' => $fulfillment->id,
        'from_driver_id' => null,
        'to_driver_id' => $this->driver->id,
        'reason' => 'Old row',
        'changed_by' => $this->staff->id,
    ]);
    $yesterday->forceFill([
        'created_at' => now()->subDay(),
        'updated_at' => now()->subDay(),
    ])->saveQuietly();

    $this->actingAs($this->staff, 'branch')
        ->get(route('branch.reports.order-assign-history', $this->branch))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('branch/reports/order-assign-history')
            ->where('table.filters.date', now()->toDateString())
            ->has('table.data', 1)
            ->where('table.data.0.id', $today->id)
            ->where('table.data.0.fulfillment_number', $fulfillment->fulfillment_number)
            ->where('table.data.0.to_driver_name', $this->driver->name));
});

test('order assign history can be searched by driver phone fragment', function () {
    $fulfillment = orderAssignHistoryFulfillment($this->branch);

    $phoneDriver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'branch_id' => null,
        'is_active' => true,
        'name' => 'Phone Search Driver',
        'phone' => '+61499887766',
    ]);

    OrderDriverAssignmentHistory::query()->create([
        'order_id' => $fulfillment->order_id,
        'fulfillment_id' => $fulfillment->id,
        'from_driver_id' => null,
        'to_driver_id' => $phoneDriver->id,
        'reason' => null,
        'changed_by' => $this->staff->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    OrderDriverAssignmentHistory::query()->create([
        'order_id' => $fulfillment->order_id,
        'fulfillment_id' => $fulfillment->id,
        'from_driver_id' => null,
        'to_driver_id' => $this->driver->id,
        'reason' => null,
        'changed_by' => $this->staff->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($this->staff, 'branch')
        ->get(route('branch.reports.order-assign-history', [
            'branch' => $this->branch,
            'filter' => [
                'search' => '998877',
                'date' => now()->toDateString(),
            ],
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('branch/reports/order-assign-history')
            ->has('table.data', 1)
            ->where('table.data.0.to_driver_phone', '+61499887766'));
});

test('order assign history can be filtered by driver', function () {
    $fulfillment = orderAssignHistoryFulfillment($this->branch);

    $secondDriver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'branch_id' => null,
        'is_active' => true,
        'name' => 'Filter Target Driver',
        'phone' => '+61400111222',
    ]);

    DriverBranchAssignment::factory()->create([
        'driver_id' => $secondDriver->id,
        'branch_id' => $this->branch->id,
        'status' => DriverAssignmentStatus::Active,
    ]);

    OrderDriverAssignmentHistory::query()->create([
        'order_id' => $fulfillment->order_id,
        'fulfillment_id' => $fulfillment->id,
        'from_driver_id' => null,
        'to_driver_id' => $this->driver->id,
        'reason' => null,
        'changed_by' => $this->staff->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $match = OrderDriverAssignmentHistory::query()->create([
        'order_id' => $fulfillment->order_id,
        'fulfillment_id' => $fulfillment->id,
        'from_driver_id' => $this->driver->id,
        'to_driver_id' => $secondDriver->id,
        'reason' => 'Swap',
        'changed_by' => $this->staff->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($this->staff, 'branch')
        ->get(route('branch.reports.order-assign-history', [
            'branch' => $this->branch,
            'filter' => [
                'driver_id' => $secondDriver->id,
                'date' => now()->toDateString(),
            ],
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('branch/reports/order-assign-history')
            ->has('table.data', 1)
            ->where('table.data.0.id', $match->id)
            ->where('table.filterOptions.selected_driver.id', $secondDriver->id)
            ->where('table.filterOptions.selected_driver.phone', '+61400111222'));
});

test('order assign history skips default today when order_id filter is set', function () {
    $fulfillment = orderAssignHistoryFulfillment($this->branch);

    $old = OrderDriverAssignmentHistory::query()->create([
        'order_id' => $fulfillment->order_id,
        'fulfillment_id' => $fulfillment->id,
        'from_driver_id' => null,
        'to_driver_id' => $this->driver->id,
        'reason' => null,
        'changed_by' => $this->staff->id,
    ]);
    $old->forceFill([
        'created_at' => now()->subDays(3),
        'updated_at' => now()->subDays(3),
    ])->saveQuietly();

    $this->actingAs($this->staff, 'branch')
        ->get(route('branch.reports.order-assign-history', [
            'branch' => $this->branch,
            'filter' => [
                'order_id' => $fulfillment->fulfillment_number,
            ],
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('branch/reports/order-assign-history')
            ->missing('table.filters.date')
            ->where('table.filters.order_id', $fulfillment->fulfillment_number)
            ->has('table.data', 1)
            ->where('table.data.0.id', $old->id)
            ->where('table.data.0.fulfillment_number', $fulfillment->fulfillment_number));
});

test('order assign history skips default today when fulfillment filter is set', function () {
    $fulfillment = orderAssignHistoryFulfillment($this->branch);

    $old = OrderDriverAssignmentHistory::query()->create([
        'order_id' => $fulfillment->order_id,
        'fulfillment_id' => $fulfillment->id,
        'from_driver_id' => null,
        'to_driver_id' => $this->driver->id,
        'reason' => null,
        'changed_by' => $this->staff->id,
    ]);
    $old->forceFill([
        'created_at' => now()->subDays(3),
        'updated_at' => now()->subDays(3),
    ])->saveQuietly();

    $this->actingAs($this->staff, 'branch')
        ->get(route('branch.reports.order-assign-history', [
            'branch' => $this->branch,
            'filter' => [
                'fulfillment_id' => $fulfillment->id,
            ],
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('branch/reports/order-assign-history')
            ->missing('table.filters.date')
            ->has('table.data', 1)
            ->where('table.data.0.id', $old->id)
            ->where('table.filterOptions.selected_fulfillment.id', $fulfillment->id));
});

test('order assign history shows System for platform actors without a link', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $fulfillment = orderAssignHistoryFulfillment($this->branch);

    OrderDriverAssignmentHistory::query()->create([
        'order_id' => $fulfillment->order_id,
        'fulfillment_id' => $fulfillment->id,
        'from_driver_id' => null,
        'to_driver_id' => $this->driver->id,
        'reason' => null,
        'changed_by' => $admin->id,
    ]);

    $this->actingAs($this->staff, 'branch')
        ->get(route('branch.reports.order-assign-history', [
            'branch' => $this->branch,
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

test('guests cannot export order assign history', function () {
    $this->get(route('branch.reports.order-assign-history.export', [
        $this->branch,
        'format' => 'csv',
        'scope' => 'all',
    ]))->assertRedirect(route('login'));
});

test('branch staff can export order assign history as csv', function () {
    $this->travelTo('2026-09-18 12:00:00');

    $fulfillment = orderAssignHistoryFulfillment($this->branch, [
        'fulfillment_number' => 'EXPORT-OA-001',
    ]);

    OrderDriverAssignmentHistory::query()->create([
        'order_id' => $fulfillment->order_id,
        'fulfillment_id' => $fulfillment->id,
        'from_driver_id' => null,
        'to_driver_id' => $this->driver->id,
        'reason' => null,
        'changed_by' => $this->staff->id,
    ]);

    $response = $this->actingAs($this->staff, 'branch')
        ->get(route('branch.reports.order-assign-history.export', [
            $this->branch,
            'format' => 'csv',
            'scope' => 'all',
        ]));

    $response->assertOk()->assertDownload('order-assign-history-all-2026-09-18.csv');

    expect(file_get_contents($response->baseResponse->getFile()->getPathname()))
        ->toContain('EXPORT-OA-001')
        ->toContain($this->driver->name)
        ->toContain('Order ID');
});
