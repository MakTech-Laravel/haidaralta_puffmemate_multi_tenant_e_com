<?php

use App\Enums\DriverAssignmentStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\PaymentStatus;
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

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);
});

function branchOrderFulfillment(Branch $branch, array $overrides = []): OrderFulfillment
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
    ], $overrides));
    Payment::factory()->create(['order_id' => $order->id]);

    return $fulfillment;
}

test('branch staff only see fulfillments for their own branch', function () {
    $admin = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $other = Branch::factory()->create();

    $mine = branchOrderFulfillment($sydney);
    branchOrderFulfillment($other);

    $this->actingAs($admin, 'branch')
        ->get(route('branch.orders.index', $sydney))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('branch/orders/index')
            ->where('table.data.0.id', $mine->id)
            ->has('table.data', 1));
});

test('branch staff cannot view another branchs fulfillment', function () {
    $admin = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $other = Branch::factory()->create();

    $fulfillment = branchOrderFulfillment($other);

    $this->actingAs($admin, 'branch')
        ->get(route('branch.orders.show', [$sydney, $fulfillment]))
        ->assertNotFound();
});

test('branch staff can update fulfillment status and it rolls up to the master order', function () {
    $admin = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();

    $fulfillment = branchOrderFulfillment($sydney, ['status' => FulfillmentStatus::Pending]);

    $this->actingAs($admin, 'branch')
        ->put(route('branch.orders.status', [$sydney, $fulfillment]), [
            'status' => FulfillmentStatus::Processing->value,
        ])
        ->assertRedirect(route('branch.orders.show', [$sydney, $fulfillment]));

    expect($fulfillment->refresh()->status)->toBe(FulfillmentStatus::Processing)
        ->and($fulfillment->order->refresh()->status->value)->toBe('processing');
});

test('branch staff can mark their fulfillment as collected', function () {
    $admin = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();

    $fulfillment = branchOrderFulfillment($sydney);

    $this->actingAs($admin, 'branch')
        ->post(route('branch.orders.paid', [$sydney, $fulfillment]))
        ->assertRedirect(route('branch.orders.show', [$sydney, $fulfillment]));

    expect($fulfillment->refresh()->isPaid())->toBeTrue()
        ->and($fulfillment->order->refresh()->payment_status)->toBe(PaymentStatus::Collected);
});

test('a second branchs staff cannot mark another branchs fulfillment as paid', function () {
    $admin = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $other = Branch::factory()->create();

    $fulfillment = branchOrderFulfillment($other);

    $this->actingAs($admin, 'branch')
        ->post(route('branch.orders.paid', [$sydney, $fulfillment]))
        ->assertNotFound();

    expect($fulfillment->refresh()->isPaid())->toBeFalse();
});

test('branch staff can retry reschedule and confirm a returned delivery', function () {
    $admin = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $driver = User::factory()->create(['user_type' => UserType::DRIVER, 'branch_id' => null]);

    $fulfillment = branchOrderFulfillment($sydney, [
        'status' => FulfillmentStatus::DeliveryFailed,
        'driver_id' => $driver->id,
    ]);

    $this->actingAs($admin, 'branch')
        ->post(route('branch.orders.retry-delivery', [$sydney, $fulfillment]))
        ->assertRedirect(route('branch.orders.show', [$sydney, $fulfillment]));

    expect($fulfillment->refresh()->status)->toBe(FulfillmentStatus::OutForDelivery);

    $fulfillment->forceFill(['status' => FulfillmentStatus::DeliveryFailed])->save();

    $this->actingAs($admin, 'branch')
        ->post(route('branch.orders.reschedule-delivery', [$sydney, $fulfillment]))
        ->assertRedirect(route('branch.orders.show', [$sydney, $fulfillment]));

    expect($fulfillment->refresh()->status)->toBe(FulfillmentStatus::Ready)
        ->and($fulfillment->driver_id)->toBeNull();

    $fulfillment->forceFill(['status' => FulfillmentStatus::DeliveryFailed, 'driver_id' => $driver->id])->save();

    $this->actingAs($admin, 'branch')
        ->post(route('branch.orders.return-delivery', [$sydney, $fulfillment]))
        ->assertRedirect(route('branch.orders.show', [$sydney, $fulfillment]));

    expect($fulfillment->refresh()->status)->toBe(FulfillmentStatus::Returning);

    $this->actingAs($admin, 'branch')
        ->post(route('branch.orders.confirm-return', [$sydney, $fulfillment]))
        ->assertRedirect(route('branch.orders.show', [$sydney, $fulfillment]));

    expect($fulfillment->refresh()->status)->toBe(FulfillmentStatus::Cancelled);
});

test('a branch cannot retry another branchs failed delivery', function () {
    $admin = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $other = Branch::factory()->create();
    $driver = User::factory()->create(['user_type' => UserType::DRIVER, 'branch_id' => null]);

    $fulfillment = branchOrderFulfillment($other, [
        'status' => FulfillmentStatus::DeliveryFailed,
        'driver_id' => $driver->id,
    ]);

    $this->actingAs($admin, 'branch')
        ->post(route('branch.orders.retry-delivery', [$sydney, $fulfillment]))
        ->assertNotFound();
});

test('branch staff can assign a ready fulfillment to a branch driver', function () {
    $admin = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $driver = User::query()->where('email', UserSeeder::DRIVER)->firstOrFail();

    $fulfillment = branchOrderFulfillment($sydney, [
        'status' => FulfillmentStatus::Ready,
        'driver_id' => null,
    ]);

    $this->actingAs($admin, 'branch')
        ->post(route('branch.orders.assign-driver', [$sydney, $fulfillment]), [
            'driver_id' => $driver->id,
        ])
        ->assertRedirect(route('branch.orders.show', [$sydney, $fulfillment]));

    expect($fulfillment->refresh()->driver_id)->toBe($driver->id)
        ->and($fulfillment->status)->toBe(FulfillmentStatus::Assigned);

    $history = OrderDriverAssignmentHistory::query()
        ->where('fulfillment_id', $fulfillment->id)
        ->first();

    expect($history)->not->toBeNull()
        ->and($history->from_driver_id)->toBeNull()
        ->and($history->to_driver_id)->toBe($driver->id)
        ->and($history->changed_by)->toBe($admin->id)
        ->and($history->reason)->toBeNull();
});

test('branch staff must provide a reason when reassigning a driver', function () {
    $admin = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $driver = User::query()->where('email', UserSeeder::DRIVER)->firstOrFail();

    $replacement = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'branch_id' => null,
        'is_active' => true,
        'name' => 'Replacement Driver',
        'phone' => '+61422220000',
    ]);
    DriverBranchAssignment::factory()->create([
        'driver_id' => $replacement->id,
        'branch_id' => $sydney->id,
        'status' => DriverAssignmentStatus::Active,
    ]);

    $fulfillment = branchOrderFulfillment($sydney, [
        'status' => FulfillmentStatus::Assigned,
        'driver_id' => $driver->id,
    ]);

    $this->actingAs($admin, 'branch')
        ->post(route('branch.orders.assign-driver', [$sydney, $fulfillment]), [
            'driver_id' => $replacement->id,
        ])
        ->assertSessionHasErrors('reason');

    expect($fulfillment->refresh()->driver_id)->toBe($driver->id);

    $this->actingAs($admin, 'branch')
        ->post(route('branch.orders.assign-driver', [$sydney, $fulfillment]), [
            'driver_id' => $replacement->id,
            'reason' => 'Original driver unavailable',
        ])
        ->assertRedirect(route('branch.orders.show', [$sydney, $fulfillment]));

    expect($fulfillment->refresh()->driver_id)->toBe($replacement->id);

    $history = OrderDriverAssignmentHistory::query()
        ->where('fulfillment_id', $fulfillment->id)
        ->latest('id')
        ->first();

    expect($history)->not->toBeNull()
        ->and($history->from_driver_id)->toBe($driver->id)
        ->and($history->to_driver_id)->toBe($replacement->id)
        ->and($history->changed_by)->toBe($admin->id)
        ->and($history->reason)->toBe('Original driver unavailable');
});

test('branch order show links to driver assignment history page', function () {
    $admin = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $driver = User::query()->where('email', UserSeeder::DRIVER)->firstOrFail();

    $fulfillment = branchOrderFulfillment($sydney, [
        'status' => FulfillmentStatus::Ready,
        'driver_id' => null,
    ]);

    $this->actingAs($admin, 'branch')
        ->post(route('branch.orders.assign-driver', [$sydney, $fulfillment]), [
            'driver_id' => $driver->id,
        ])
        ->assertRedirect();

    $this->actingAs($admin, 'branch')
        ->get(route('branch.orders.show', [$sydney, $fulfillment]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('branch/orders/show')
            ->missing('fulfillment.driverAssignmentHistory'));

    $this->actingAs($admin, 'branch')
        ->get(route('branch.orders.driver-assignment-history', [$sydney, $fulfillment]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('branch/orders/driver-assignment-history')
            ->has('history', 1)
            ->where('history.0.to_driver_name', $driver->name)
            ->where('history.0.to_driver_phone', $driver->phone)
            ->where('history.0.to_driver_id', $driver->id)
            ->where('history.0.changed_by_name', $admin->name)
            ->where('history.0.from_driver_name', null)
            ->where('history.0.from_driver_id', null));
});

test('branch staff cannot reassign with a reason longer than 255 characters', function () {
    $admin = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $driver = User::query()->where('email', UserSeeder::DRIVER)->firstOrFail();

    $replacement = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'branch_id' => null,
        'is_active' => true,
        'name' => 'Long Reason Driver',
        'phone' => '+61433330000',
    ]);
    DriverBranchAssignment::factory()->create([
        'driver_id' => $replacement->id,
        'branch_id' => $sydney->id,
        'status' => DriverAssignmentStatus::Active,
    ]);

    $fulfillment = branchOrderFulfillment($sydney, [
        'status' => FulfillmentStatus::Assigned,
        'driver_id' => $driver->id,
    ]);

    $this->actingAs($admin, 'branch')
        ->post(route('branch.orders.assign-driver', [$sydney, $fulfillment]), [
            'driver_id' => $replacement->id,
            'reason' => str_repeat('a', 256),
        ])
        ->assertSessionHasErrors('reason');

    expect($fulfillment->refresh()->driver_id)->toBe($driver->id);
});

test('branch staff can assign a pending fulfillment to a branch driver', function () {
    $admin = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $driver = User::query()->where('email', UserSeeder::DRIVER)->firstOrFail();

    $fulfillment = branchOrderFulfillment($sydney, [
        'status' => FulfillmentStatus::Pending,
        'driver_id' => null,
    ]);

    $this->actingAs($admin, 'branch')
        ->post(route('branch.orders.assign-driver', [$sydney, $fulfillment]), [
            'driver_id' => $driver->id,
        ])
        ->assertRedirect(route('branch.orders.show', [$sydney, $fulfillment]));

    expect($fulfillment->refresh()->driver_id)->toBe($driver->id)
        ->and($fulfillment->status)->toBe(FulfillmentStatus::Assigned);
});

test('branch staff cannot assign a driver after the fulfillment is delivered', function () {
    $admin = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $driver = User::query()->where('email', UserSeeder::DRIVER)->firstOrFail();

    $fulfillment = branchOrderFulfillment($sydney, [
        'status' => FulfillmentStatus::Delivered,
        'driver_id' => null,
    ]);

    $this->actingAs($admin, 'branch')
        ->post(route('branch.orders.assign-driver', [$sydney, $fulfillment]), [
            'driver_id' => $driver->id,
        ])
        ->assertSessionHasErrors('driver_id');

    expect($fulfillment->refresh()->driver_id)->toBeNull()
        ->and($fulfillment->status)->toBe(FulfillmentStatus::Delivered);
});

test('branch orders index includes assign status for unassigned and assigned rows', function () {
    $admin = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $driver = User::query()->where('email', UserSeeder::DRIVER)->firstOrFail();

    $unassigned = branchOrderFulfillment($sydney, [
        'status' => FulfillmentStatus::Ready,
        'driver_id' => null,
    ]);
    $assigned = branchOrderFulfillment($sydney, [
        'status' => FulfillmentStatus::Assigned,
        'driver_id' => $driver->id,
    ]);

    $this->actingAs($admin, 'branch')
        ->get(route('branch.orders.index', $sydney))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('branch/orders/index')
            ->has('table.data', 2)
            ->where('table.data', function ($rows) use ($unassigned, $assigned, $driver): bool {
                $byId = collect($rows)->keyBy('id');

                $open = $byId->get($unassigned->id);
                $taken = $byId->get($assigned->id);

                return $open !== null
                    && $taken !== null
                    && $open['assign_status'] === 'not_assign'
                    && $open['driver_id'] === null
                    && $open['driver_name'] === null
                    && $open['can_assign'] === true
                    && $taken['assign_status'] === 'assign'
                    && $taken['driver_id'] === $driver->id
                    && $taken['driver_name'] === $driver->name
                    && $taken['can_assign'] === true;
            }));
});

test('branch staff can load assign candidates for a ready fulfillment', function () {
    $admin = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $driver = User::query()->where('email', UserSeeder::DRIVER)->firstOrFail();

    $fulfillment = branchOrderFulfillment($sydney, [
        'status' => FulfillmentStatus::Ready,
        'driver_id' => null,
    ]);

    $this->actingAs($admin, 'branch')
        ->getJson(route('branch.orders.assign-candidates', [$sydney, $fulfillment]))
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'phone', 'avatar_url'],
            ],
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            'current_driver_id',
        ])
        ->assertJsonFragment([
            'id' => $driver->id,
            'name' => $driver->name,
            'phone' => $driver->phone,
        ]);
});

test('branch staff can search assign candidates by phone', function () {
    $admin = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $driver = User::query()->where('email', UserSeeder::DRIVER)->firstOrFail();

    $other = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'branch_id' => null,
        'is_active' => true,
        'name' => 'Other Sydney Driver',
        'phone' => '+61499990000',
    ]);
    DriverBranchAssignment::factory()->create([
        'driver_id' => $other->id,
        'branch_id' => $sydney->id,
        'status' => DriverAssignmentStatus::Active,
    ]);

    $fulfillment = branchOrderFulfillment($sydney, [
        'status' => FulfillmentStatus::Ready,
        'driver_id' => null,
    ]);

    $response = $this->actingAs($admin, 'branch')
        ->getJson(route('branch.orders.assign-candidates', [
            'branch' => $sydney,
            'fulfillment' => $fulfillment,
            'filter' => ['search' => '488888888'],
        ]))
        ->assertOk();

    $ids = collect($response->json('data'))->pluck('id');

    expect($ids)->toContain($driver->id)
        ->and($ids)->not->toContain($other->id)
        ->and($response->json('meta.total'))->toBe(1);
});

test('branch staff can paginate assign candidates', function () {
    $admin = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();

    foreach (range(1, 10) as $index) {
        $extra = User::factory()->create([
            'user_type' => UserType::DRIVER,
            'branch_id' => null,
            'is_active' => true,
            'name' => sprintf('Zulu Assign Driver %02d', $index),
            'phone' => sprintf('+614777700%02d', $index),
        ]);
        DriverBranchAssignment::factory()->create([
            'driver_id' => $extra->id,
            'branch_id' => $sydney->id,
            'status' => DriverAssignmentStatus::Active,
        ]);
    }

    $fulfillment = branchOrderFulfillment($sydney, [
        'status' => FulfillmentStatus::Ready,
        'driver_id' => null,
    ]);

    $response = $this->actingAs($admin, 'branch')
        ->getJson(route('branch.orders.assign-candidates', [
            'branch' => $sydney,
            'fulfillment' => $fulfillment,
            'per_page' => 10,
            'page' => 1,
        ]))
        ->assertOk()
        ->assertJsonCount(10, 'data')
        ->assertJsonPath('meta.per_page', 10)
        ->assertJsonPath('meta.current_page', 1);

    expect($response->json('meta.last_page'))->toBeGreaterThanOrEqual(2)
        ->and($response->json('meta.total'))->toBeGreaterThanOrEqual(11);
});

test('branch staff can load assign candidates for a pending fulfillment', function () {
    $admin = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();

    $fulfillment = branchOrderFulfillment($sydney, [
        'status' => FulfillmentStatus::Pending,
        'driver_id' => null,
    ]);

    $this->actingAs($admin, 'branch')
        ->getJson(route('branch.orders.assign-candidates', [$sydney, $fulfillment]))
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'phone', 'avatar_url'],
            ],
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            'current_driver_id',
        ]);
});

test('branch staff cannot load assign candidates after the fulfillment is delivered', function () {
    $admin = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();

    $fulfillment = branchOrderFulfillment($sydney, [
        'status' => FulfillmentStatus::Delivered,
        'driver_id' => null,
    ]);

    $this->actingAs($admin, 'branch')
        ->getJson(route('branch.orders.assign-candidates', [$sydney, $fulfillment]))
        ->assertStatus(422);
});

test('branch orders index marks pending rows as assignable and delivered as not', function () {
    $admin = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();

    $pending = branchOrderFulfillment($sydney, [
        'status' => FulfillmentStatus::Pending,
        'driver_id' => null,
    ]);
    $delivered = branchOrderFulfillment($sydney, [
        'status' => FulfillmentStatus::Delivered,
        'driver_id' => null,
    ]);

    $this->actingAs($admin, 'branch')
        ->get(route('branch.orders.index', $sydney))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('branch/orders/index')
            ->where('table.data', function ($rows) use ($pending, $delivered): bool {
                $byId = collect($rows)->keyBy('id');

                return ($byId->get($pending->id)['can_assign'] ?? null) === true
                    && ($byId->get($delivered->id)['can_assign'] ?? null) === false;
            }));
});
