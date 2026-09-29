<?php

use App\Enums\FulfillmentStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\Order;
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
