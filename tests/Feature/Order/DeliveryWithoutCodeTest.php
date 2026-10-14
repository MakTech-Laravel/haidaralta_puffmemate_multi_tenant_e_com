<?php

use App\Enums\FulfillmentStatus;
use App\Enums\HandoverVerification;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\Order;
use App\Models\OrderFulfillment;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\User;
use App\Models\UserNotification;
use App\Support\PlatformTeamResolver;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);

    app(PermissionRegistrar::class)->setPermissionsTeamId(PlatformTeamResolver::PLATFORM_TEAM_ID);
});

function arrivedOrderAtDoor(FulfillmentStatus $status = FulfillmentStatus::Arrived): Order
{
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'email_verified_at' => now(),
    ]);
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $order = Order::factory()->create([
        'status' => OrderStatus::from($status->value),
        'shipping_address_snapshot' => ['phone' => '+61410111222', 'name' => 'Ada'],
    ]);
    OrderFulfillment::factory()->forOrder($order)->create([
        'branch_id' => $sydney->id,
        'driver_id' => $driver->id,
        'status' => $status,
    ]);
    Payment::factory()->create([
        'order_id' => $order->id,
        'status' => PaymentStatus::PendingCollection,
        'gateway' => 'cod',
    ]);

    return $order->refresh();
}

test('a platform manager can complete an arrived order without the code', function () {
    $manager = User::query()->where('email', UserSeeder::PLATFORM_MANAGER)->firstOrFail();
    $order = arrivedOrderAtDoor();
    $fulfillment = $order->fulfillment;

    $this->actingAs($manager, 'platform')
        ->get(route('platform.orders.show', $order))
        ->assertInertia(fn (Assert $page) => $page->where('order.canCompleteWithoutCode', true));

    $this->actingAs($manager, 'platform')
        ->post(route('platform.orders.deliver-without-code', $order), [
            'method' => HandoverVerification::PhoneCall->value,
            'note' => 'Called the number on the order and the customer confirmed',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('platform.orders.show', $order));

    $payment = Payment::query()->where('order_id', $order->id)->sole();
    $history = OrderStatusHistory::query()->where('fulfillment_id', $fulfillment->id)->latest('id')->first();

    expect($fulfillment->fresh()->status)->toBe(FulfillmentStatus::Delivered)
        ->and($payment->status)->toBe(PaymentStatus::Collected)
        ->and($payment->collected_by)->toBe($fulfillment->driver_id)
        ->and($history->changed_by)->toBe($manager->id)
        ->and($history->comment)->toBe('Delivered without code: Called the customer. Called the number on the order and the customer confirmed')
        ->and(UserNotification::query()
            ->where('user_id', $order->customer_id)
            ->whereHas('notification', fn ($query) => $query->where('title', 'Order delivered'))
            ->exists())->toBeTrue();
});

test('a platform user without the override permission cannot complete without the code', function () {
    Role::findByName(RoleEnum::MANAGER->value, 'platform')
        ->revokePermissionTo(PermissionEnum::COMPLETE_ORDERS_WITHOUT_CODE->value);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $manager = User::query()->where('email', UserSeeder::PLATFORM_MANAGER)->firstOrFail();
    $order = arrivedOrderAtDoor();

    $this->actingAs($manager, 'platform')
        ->get(route('platform.orders.show', $order))
        ->assertInertia(fn (Assert $page) => $page->where('order.canCompleteWithoutCode', false));

    $this->actingAs($manager, 'platform')
        ->post(route('platform.orders.deliver-without-code', $order), [
            'method' => HandoverVerification::PhoneCall->value,
            'note' => 'Customer confirmed by phone',
        ])
        ->assertForbidden();

    expect($order->fulfillment->fresh()->status)->toBe(FulfillmentStatus::Arrived);
});

test('completing without the code requires a verification method and a note', function () {
    $manager = User::query()->where('email', UserSeeder::PLATFORM_MANAGER)->firstOrFail();
    $order = arrivedOrderAtDoor();

    $this->actingAs($manager, 'platform')
        ->post(route('platform.orders.deliver-without-code', $order), [
            'method' => 'trust_me',
            'note' => '',
        ])
        ->assertSessionHasErrors(['method', 'note']);

    expect($order->fulfillment->fresh()->status)->toBe(FulfillmentStatus::Arrived);
});

test('an order can be completed without the code only after the driver arrives', function () {
    $manager = User::query()->where('email', UserSeeder::PLATFORM_MANAGER)->firstOrFail();
    $order = arrivedOrderAtDoor(FulfillmentStatus::OutForDelivery);

    $this->actingAs($manager, 'platform')
        ->post(route('platform.orders.deliver-without-code', $order), [
            'method' => HandoverVerification::PhotoId->value,
            'note' => 'Checked the licence over video',
        ])
        ->assertSessionHasErrors('status');

    expect($order->fulfillment->fresh()->status)->toBe(FulfillmentStatus::OutForDelivery);
});

test('a branch admin can complete an arrived order without the code', function () {
    $admin = User::query()->where('email', UserSeeder::BRANCH_1_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $fulfillment = arrivedOrderAtDoor()->fulfillment;

    $this->actingAs($admin, 'branch')
        ->post(route('branch.orders.deliver-without-code', [$sydney, $fulfillment]), [
            'method' => HandoverVerification::PhotoId->value,
            'note' => 'Checked the driver licence matches the order name',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('branch.orders.show', [$sydney, $fulfillment]));

    expect($fulfillment->fresh()->status)->toBe(FulfillmentStatus::Delivered);
});

test('branch staff cannot complete an order without the code', function () {
    $staff = User::query()->where('email', UserSeeder::BRANCH_1_STAFF)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $fulfillment = arrivedOrderAtDoor()->fulfillment;

    $this->actingAs($staff, 'branch')
        ->post(route('branch.orders.deliver-without-code', [$sydney, $fulfillment]), [
            'method' => HandoverVerification::PhoneCall->value,
            'note' => 'Customer confirmed by phone',
        ])
        ->assertForbidden();

    expect($fulfillment->fresh()->status)->toBe(FulfillmentStatus::Arrived);
});
