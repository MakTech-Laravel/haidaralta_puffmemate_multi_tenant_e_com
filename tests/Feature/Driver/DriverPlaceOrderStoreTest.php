<?php

use App\Enums\CustomerRegistrationStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\InventoryTransactionType;
use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\DriverBranchAssignment;
use App\Models\DriverInventory;
use App\Models\DriverInventoryTransaction;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Services\Application\PaymentGatewaySettingsService;
use App\Services\Customer\CustomerResolutionService;
use App\Services\Order\FulfillmentStatusService;
use App\Services\Sms\FakeSmsSender;
use App\Support\Phone;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    app(PaymentGatewaySettingsService::class)->ensureDefaults();
});

function placeOrderDriver(): array
{
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'branch_id' => null,
    ]);
    $branch = Branch::factory()->create();
    DriverBranchAssignment::factory()->create([
        'driver_id' => $driver->id,
        'branch_id' => $branch->id,
    ]);
    $product = Product::factory()->create([
        'status' => true,
        'selling_price' => 12.5,
    ]);
    DriverInventory::factory()->create([
        'user_id' => $driver->id,
        'branch_id' => $branch->id,
        'product_id' => $product->id,
        'variant_id' => null,
        'quantity' => 4,
    ]);

    return [$driver, $branch, $product];
}

test('customer search matches name email and phone', function () {
    $customer = User::factory()->create([
        'name' => 'Searchable Buyer',
        'user_type' => UserType::CUSTOMER,
        'phone' => Phone::toE164('0499000001'),
        'email' => 'searchable.buyer@example.com',
        'registration_status' => CustomerRegistrationStatus::Registered,
    ]);
    User::factory()->create([
        'name' => 'Other Buyer',
        'user_type' => UserType::CUSTOMER,
        'phone' => Phone::toE164('0499000099'),
    ]);
    [$driver] = placeOrderDriver();

    $this->actingAs($driver, 'driver')
        ->postJson(route('driver.place-order.customers.lookup'), [
            'q' => '0499 000 001',
        ])
        ->assertOk()
        ->assertJsonCount(1, 'customers')
        ->assertJsonPath('customers.0.id', $customer->id)
        ->assertJsonPath('customers.0.registration_status', CustomerRegistrationStatus::Registered->value)
        ->assertJsonPath('customers.0.email', 'searchable.buyer@example.com');

    $this->actingAs($driver, 'driver')
        ->postJson(route('driver.place-order.customers.lookup'), [
            'q' => 'Searchable Buyer',
        ])
        ->assertOk()
        ->assertJsonCount(1, 'customers')
        ->assertJsonPath('customers.0.id', $customer->id);

    $this->actingAs($driver, 'driver')
        ->postJson(route('driver.place-order.customers.lookup'), [
            'q' => 'searchable.buyer@example.com',
        ])
        ->assertOk()
        ->assertJsonCount(1, 'customers')
        ->assertJsonPath('customers.0.id', $customer->id);
});

test('customer search returns empty list when nothing matches', function () {
    [$driver] = placeOrderDriver();

    $this->actingAs($driver, 'driver')
        ->postJson(route('driver.place-order.customers.lookup'), [
            'q' => 'zz-no-such-customer',
        ])
        ->assertOk()
        ->assertJsonPath('customers', []);
});

test('a new phone creates one provisional customer and cannot log in', function () {
    [$driver, $branch, $product] = placeOrderDriver();

    $this->actingAs($driver, 'driver')
        ->post(route('driver.place-order.store'), [
            'phone' => '0499000002',
            'name' => 'Walk Up',
            'payment_method' => 'cod',
            'idempotency_key' => (string) str()->uuid(),
            'items' => [
                ['id' => (string) $product->id, 'quantity' => 1, 'price' => 1],
            ],
        ])
        ->assertRedirect();

    $customer = User::query()->where('name', 'Walk Up')->firstOrFail();

    expect($customer->registration_status)->toBe(CustomerRegistrationStatus::Provisional)
        ->and($customer->password)->toBeNull()
        ->and($customer->phone_verified_at)->toBeNull()
        ->and(User::query()->where('phone', $customer->phone)->count())->toBe(1);

    $this->post(route('login.store'), [
        'phone' => $customer->phone,
        'password' => 'password',
    ])->assertSessionHasErrors('phone');

    $order = Order::query()->firstOrFail();

    expect($order->source)->toBe(OrderSource::DriverSale)
        ->and($order->placed_by)->toBe($driver->id)
        ->and($order->status)->toBe(OrderStatus::Delivered)
        ->and((float) $order->total_amount)->toBe(12.5)
        ->and($order->fulfillment->driver_id)->toBeNull()
        ->and($order->fulfillment->branch_id)->toBe($branch->id)
        ->and($order->payment_status)->toBe(PaymentStatus::PendingCollection);

    expect(DriverInventory::query()->where('product_id', $product->id)->value('quantity'))->toBe(3);
    expect(DriverInventoryTransaction::query()->where('type', InventoryTransactionType::Sale)->where('reference_id', $order->id)->count())->toBe(1);
});

test('selling the same phone again reuses the provisional customer', function () {
    $existing = User::factory()->provisional()->create(['name' => 'Kept Name']);
    [$driver, , $product] = placeOrderDriver();

    $this->actingAs($driver, 'driver')
        ->post(route('driver.place-order.store'), [
            'phone' => $existing->phone,
            'name' => 'Different Name',
            'payment_method' => 'pos',
            'idempotency_key' => (string) str()->uuid(),
            'items' => [
                ['id' => (string) $product->id, 'quantity' => 1],
            ],
        ])
        ->assertRedirect();

    expect(User::query()->where('phone', $existing->phone)->count())->toBe(1)
        ->and($existing->refresh()->name)->toBe('Kept Name');

    $payment = Payment::query()->firstOrFail();

    expect($payment->status)->toBe(PaymentStatus::Collected)
        ->and($payment->collected_by)->toBe($driver->id);
});

test('a repeated idempotency key does not sell the stock twice', function () {
    [$driver, , $product] = placeOrderDriver();
    $key = (string) str()->uuid();
    $payload = [
        'phone' => '0499000003',
        'name' => 'Retry',
        'payment_method' => 'cod',
        'idempotency_key' => $key,
        'items' => [
            ['id' => (string) $product->id, 'quantity' => 1],
        ],
    ];

    $this->actingAs($driver, 'driver')->post(route('driver.place-order.store'), $payload)->assertRedirect();
    $this->actingAs($driver, 'driver')->post(route('driver.place-order.store'), $payload)->assertRedirect();

    expect(Order::query()->count())->toBe(1)
        ->and(DriverInventory::query()->value('quantity'))->toBe(3);
});

test('a driver cannot sell another drivers stock or more than they hold', function () {
    [$driver, $branch, $product] = placeOrderDriver();
    $other = User::factory()->create(['user_type' => UserType::DRIVER, 'branch_id' => null]);
    $foreign = Product::factory()->create(['status' => true, 'selling_price' => 9]);
    DriverInventory::factory()->create([
        'user_id' => $other->id,
        'branch_id' => $branch->id,
        'product_id' => $foreign->id,
        'variant_id' => null,
        'quantity' => 5,
    ]);

    $this->actingAs($driver, 'driver')
        ->post(route('driver.place-order.store'), [
            'phone' => '0499000004',
            'name' => 'Nope',
            'payment_method' => 'cod',
            'idempotency_key' => (string) str()->uuid(),
            'items' => [
                ['id' => (string) $foreign->id, 'quantity' => 1],
            ],
        ])
        ->assertSessionHasErrors('items');

    $this->actingAs($driver, 'driver')
        ->post(route('driver.place-order.store'), [
            'phone' => '0499000004',
            'name' => 'Nope',
            'payment_method' => 'cod',
            'idempotency_key' => (string) str()->uuid(),
            'items' => [
                ['id' => (string) $product->id, 'quantity' => 9],
            ],
        ])
        ->assertSessionHasErrors('items');

    expect(DriverInventory::query()->where('user_id', $driver->id)->value('quantity'))->toBe(4)
        ->and(Order::query()->count())->toBe(0);
});

test('stock on a second active branch is not sold', function () {
    [$driver, $branch, $product] = placeOrderDriver();
    $otherBranch = Branch::factory()->create();
    DriverBranchAssignment::factory()->create([
        'driver_id' => $driver->id,
        'branch_id' => $otherBranch->id,
    ]);

    $this->actingAs($driver, 'driver')
        ->post(route('driver.place-order.store'), [
            'phone' => '0499000005',
            'name' => 'Split',
            'payment_method' => 'cod',
            'idempotency_key' => (string) str()->uuid(),
            'items' => [
                ['id' => (string) $product->id, 'quantity' => 1],
            ],
        ])
        ->assertSessionHasErrors('items');

    expect(DriverInventory::query()->where('branch_id', $branch->id)->value('quantity'))->toBe(4);
});

test('cancelling a delivered driver sale does not return stock', function () {
    [$driver, , $product] = placeOrderDriver();

    $this->actingAs($driver, 'driver')
        ->post(route('driver.place-order.store'), [
            'phone' => '0499000006',
            'name' => 'Done',
            'payment_method' => 'cod',
            'idempotency_key' => (string) str()->uuid(),
            'items' => [
                ['id' => (string) $product->id, 'quantity' => 2],
            ],
        ])
        ->assertRedirect();

    $order = Order::query()->with('fulfillment')->firstOrFail();

    expect(fn () => app(FulfillmentStatusService::class)->cancel($order->fulfillment, $driver))
        ->toThrow(ValidationException::class);

    expect(DriverInventory::query()->value('quantity'))->toBe(2);
});

test('cancelling a driver sale before it is terminal returns bag stock once', function () {
    [$driver, , $product] = placeOrderDriver();

    $this->actingAs($driver, 'driver')
        ->post(route('driver.place-order.store'), [
            'phone' => '0499000007',
            'name' => 'Hold',
            'payment_method' => 'cod',
            'idempotency_key' => (string) str()->uuid(),
            'items' => [
                ['id' => (string) $product->id, 'quantity' => 2],
            ],
        ])
        ->assertRedirect();

    $order = Order::query()->with('fulfillment')->firstOrFail();
    $order->forceFill(['status' => OrderStatus::Pending])->save();
    $order->fulfillment->forceFill(['status' => FulfillmentStatus::Pending])->save();

    app(FulfillmentStatusService::class)->cancel($order->fulfillment->refresh(), $driver);

    expect(DriverInventory::query()->value('quantity'))->toBe(4)
        ->and(DriverInventoryTransaction::query()->where('type', InventoryTransactionType::StockIn)->count())->toBe(1);

    expect(fn () => app(FulfillmentStatusService::class)->cancel($order->fulfillment->refresh(), $driver))
        ->toThrow(ValidationException::class);

    expect(DriverInventory::query()->value('quantity'))->toBe(4);
});

test('provisional create races collapse to one customer', function () {
    $service = app(CustomerResolutionService::class);
    $phone = Phone::toE164('0499000008');

    $first = $service->createProvisional($phone, 'First');
    $second = $service->createProvisional($phone, 'Second');

    expect($second->id)->toBe($first->id)
        ->and(User::query()->where('phone', $phone)->count())->toBe(1);
});

test('registering a provisional phone claims it after the code instead of creating another user', function () {
    $customer = User::factory()->provisional()->create();
    Order::factory()->create(['customer_id' => $customer->id]);

    $this->post(route('register.store'), [
        'name' => 'Claimed Name',
        'phone' => $customer->phone,
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect(route('register.claim'));

    expect(User::query()->where('phone', $customer->phone)->count())->toBe(1)
        ->and($customer->refresh()->isRegisteredCustomer())->toBeFalse();

    $this->post(route('login.store'), [
        'phone' => $customer->phone,
        'password' => 'password',
    ])->assertSessionHasErrors('phone');

    $code = app(FakeSmsSender::class)->lastCode();

    $this->post(route('register.claim.store'), [
        'code' => $code,
    ])->assertRedirect(route('dashboard'));

    expect($customer->refresh()->isRegisteredCustomer())->toBeTrue()
        ->and($customer->name)->toBe('Claimed Name')
        ->and(Order::query()->where('customer_id', $customer->id)->count())->toBe(1);
});
