<?php

use App\Enums\FulfillmentStatus;
use App\Enums\PaymentStatus;
use App\Enums\ShiftAssignmentStatus;
use App\Models\Branch;
use App\Models\Order;
use App\Models\OrderFulfillment;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ShiftTemplate;
use App\Models\User;
use App\Models\UserShiftAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

// Order services are pure logic wrapped around Eloquent models (Branch,
// Inventory, Order) — they need the database but not HTTP, so they live in
// Unit/Services/Order and still get RefreshDatabase.
pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Unit/Services');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

function scheduleDriverOnDuty(User $driver, Branch $branch): void
{
    $template = ShiftTemplate::factory()->create([
        'start_time' => '00:00',
        'end_time' => '23:59',
    ]);

    UserShiftAssignment::factory()->create([
        'user_id' => $driver->id,
        'branch_id' => $branch->id,
        'shift_template_id' => $template->id,
        'start_date' => now()->toDateString(),
        'end_date' => now()->toDateString(),
        'status' => ShiftAssignmentStatus::Scheduled,
    ]);
}

/**
 * @param  array<string, mixed>  $fulfillment
 * @param  array<string, mixed>  $order
 */
function driverPortalOrder(User $driver, array $fulfillment = [], array $order = []): Order
{
    $orderModel = Order::factory()->create(array_merge([
        'shipping_address_snapshot' => [
            'name' => 'Elena Vargas',
            'phone' => '+61410123456',
            'address' => '418 Crown St, Surry Hills',
            'city' => 'Surry Hills',
            'postal_code' => '2010',
        ],
        'total_amount' => 40,
        'payment_method' => 'cod',
        'time_slot' => 'Anytime',
    ], $order));

    $fulfillmentModel = OrderFulfillment::factory()->forOrder($orderModel)->create(array_merge([
        'driver_id' => $driver->id,
        'status' => FulfillmentStatus::Assigned,
    ], $fulfillment));

    $product = Product::factory()->create(['name' => 'Test Stick']);
    OrderItem::query()->create([
        'order_id' => $orderModel->id,
        'fulfillment_id' => $fulfillmentModel->id,
        'product_id' => $product->id,
        'variant_id' => null,
        'product_name_snapshot' => $product->name,
        'sku_snapshot' => $product->sku,
        'product_snapshot' => ['name' => $product->name],
        'quantity' => 1,
        'unit_price' => 40,
        'subtotal' => 40,
    ]);
    Payment::factory()->create([
        'order_id' => $orderModel->id,
        'status' => PaymentStatus::PendingCollection,
        'gateway' => 'cod',
        'amount' => 40,
    ]);

    return $orderModel->refresh();
}

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}
