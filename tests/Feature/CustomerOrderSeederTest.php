<?php

use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Address;
use App\Models\Order;
use App\Models\OrderFulfillment;
use App\Models\User;
use Database\Seeders\BranchInventorySeeder;
use Database\Seeders\BranchSeeder;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\CheckoutSettingsSeeder;
use Database\Seeders\CustomerOrderSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::fake([
        'images.unsplash.com/*' => Http::response(
            file_get_contents(public_path('images/storefront/logo.png')),
            200,
            ['Content-Type' => 'image/png'],
        ),
    ]);

    $this->seed([
        RolesAndPermissionsSeeder::class,
        BranchSeeder::class,
        UserSeeder::class,
        CatalogSeeder::class,
        CheckoutSettingsSeeder::class,
        BranchInventorySeeder::class,
        CustomerOrderSeeder::class,
    ]);
});

test('customer order seeder gives each demo customer orders in every status', function () {
    $customer = User::query()->where('phone', UserSeeder::CUSTOMER_PHONE)->firstOrFail();

    $statuses = Order::query()
        ->where('customer_id', $customer->id)
        ->get()
        ->countBy(fn (Order $order): string => $order->status->value)
        ->all();

    expect($statuses)->toEqual([
        OrderStatus::Pending->value => 2,
        OrderStatus::Hold->value => 1,
        OrderStatus::Placed->value => 2,
        OrderStatus::Processing->value => 2,
        OrderStatus::Ready->value => 2,
        OrderStatus::Assigned->value => 4,
        OrderStatus::PickedUp->value => 2,
        OrderStatus::OutForDelivery->value => 2,
        OrderStatus::Arrived->value => 2,
        OrderStatus::DeliveryFailed->value => 2,
        OrderStatus::Returning->value => 2,
        OrderStatus::Returned->value => 2,
        OrderStatus::Delivered->value => 2,
        OrderStatus::Cancelled->value => 2,
    ]);

    foreach (UserSeeder::CUSTOMERS_WITH_ORDERS_PHONES as $phone) {
        $orderCount = Order::query()
            ->whereHas('customer', fn ($query) => $query->where('phone', $phone))
            ->count();

        expect($orderCount)->toBe(29);
    }

    expect(Order::query()
        ->whereHas('customer', fn ($query) => $query->where('phone', UserSeeder::CUSTOMER_WITHOUT_ORDERS_PHONE))
        ->exists())->toBeFalse()
        ->and(Address::query()->where('user_id', $customer->id)->count())->toBe(1);
});

test('customer order seeder gives the driver work to Daniel except two assigned orders per customer', function () {
    $daniel = User::query()->where('phone', UserSeeder::DRIVER_PHONE)->firstOrFail();
    $marcus = User::query()->where('phone', UserSeeder::SECOND_DRIVER_PHONE)->firstOrFail();
    $customerCount = count(UserSeeder::CUSTOMERS_WITH_ORDERS_PHONES);

    $driverWork = OrderFulfillment::query()
        ->whereIn('status', FulfillmentStatus::driverWorkload())
        ->get();

    expect($driverWork->where('driver_id', $marcus->id)->count())->toBe(2 * $customerCount)
        ->and($driverWork->where('driver_id', $marcus->id)->pluck('status')->unique()->all())->toEqual([FulfillmentStatus::Assigned])
        ->and($driverWork->where('driver_id', $daniel->id)->count())->toBe(12 * $customerCount)
        ->and($driverWork->whereNotIn('driver_id', [$daniel->id, $marcus->id])->count())->toBe(0);
});

test('customer order seeder delivers with a collected payment and does not duplicate on rerun', function () {
    $delivered = Order::query()
        ->where('status', OrderStatus::Delivered)
        ->with('payments')
        ->get();

    expect($delivered)->toHaveCount(2 * count(UserSeeder::CUSTOMERS_WITH_ORDERS_PHONES))
        ->and($delivered->every(fn (Order $order): bool => $order->payment_status === PaymentStatus::Collected))->toBeTrue();

    $total = Order::query()->count();

    $this->seed(CustomerOrderSeeder::class);

    expect(Order::query()->count())->toBe($total);
});
