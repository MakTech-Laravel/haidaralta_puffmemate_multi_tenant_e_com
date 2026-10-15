<?php

use App\Contracts\SendsSms;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserType;
use App\Enums\WholesaleInquiryStatus;
use App\Models\Branch;
use App\Models\BranchProductPrice;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderFulfillment;
use App\Models\PaymentGatewaySetting;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\UserNotification;
use App\Models\WholesaleInquiry;
use App\Services\Application\PaymentGatewaySettingsService;
use App\Services\Catalog\DeliverySettingsService;
use App\Services\Order\DriverAssignmentService;
use App\Services\Order\DriverDeliveryService;
use App\Services\Order\FulfillmentStatusService;
use App\Services\Sms\FakeSmsSender;
use App\Support\Phone;
use Database\Seeders\ApplicationSettingsSeeder;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);
    $this->seed(ApplicationSettingsSeeder::class);
    app(PaymentGatewaySettingsService::class)->ensureDefaults();
    app(DeliverySettingsService::class)->ensureDefaults();

    $this->admin = User::query()->where('email', UserSeeder::PLATFORM_ADMIN)->firstOrFail();
    $this->sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $this->product = Product::factory()->create([
        'status' => true,
        'name' => 'Wholesale Carton',
        'selling_price' => 30,
        'wholesale_price' => 22,
    ]);
    $this->inventory = Inventory::factory()->create([
        'branch_id' => $this->sydney->id,
        'product_id' => $this->product->id,
        'quantity' => 100,
        'reserved_quantity' => 0,
        'status' => true,
    ]);
});

/**
 * @return array<string, mixed>
 */
function wholesaleOrderPayload(array $overrides = []): array
{
    return [
        'name' => 'Harbour Traders',
        'phone' => '0412000111',
        'email' => 'orders@harbour.test',
        'company' => 'Harbour Traders Pty Ltd',
        'address' => '200 George St, Sydney NSW',
        'latitude' => -33.8650,
        'longitude' => 151.2094,
        'branch_id' => test()->sydney->id,
        'items' => [
            ['product_id' => test()->product->id, 'quantity' => 40, 'unit_price' => 20],
        ],
        'payment_method' => PaymentGatewaySetting::KEY_COD,
        'idempotency_key' => (string) Str::uuid(),
        ...$overrides,
    ];
}

test('admin places a wholesale order from an inquiry', function () {
    $inquiry = WholesaleInquiry::factory()->create(['phone' => '0412000111']);

    $this->actingAs($this->admin, 'platform')
        ->post(route('platform.wholesale-orders.store'), wholesaleOrderPayload([
            'wholesale_inquiry_id' => $inquiry->id,
            'discount' => 50,
            'delivery_fee' => 15,
            'notes' => 'Back dock, before 10am.',
        ]))
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $order = Order::query()->with(['fulfillments', 'items', 'payment'])->firstOrFail();
    $fulfillment = $order->fulfillments->first();
    $customer = User::query()->findOrFail($order->customer_id);

    expect($order->source)->toBe(OrderSource::Wholesale)
        ->and($order->status)->toBe(OrderStatus::Placed)
        ->and($order->placed_by)->toBe($this->admin->id)
        ->and((float) $order->subtotal)->toBe(800.0)
        ->and((float) $order->discount)->toBe(50.0)
        ->and((float) $order->delivery_fee)->toBe(15.0)
        ->and((float) $order->total_amount)->toBe(765.0)
        ->and($order->notes)->toBe('Back dock, before 10am.')
        ->and($order->shipping_address_snapshot['company'])->toBe('Harbour Traders Pty Ltd')
        ->and($order->order_number)->toMatch('/^PMM-\d{4}-\d{4}$/')
        ->and($fulfillment->branch_id)->toBe($this->sydney->id)
        ->and($fulfillment->status)->toBe(FulfillmentStatus::Placed)
        ->and($order->items)->toHaveCount(1)
        ->and((float) $order->items->first()->unit_price)->toBe(20.0)
        ->and($order->payment->status)->toBe(PaymentStatus::PendingCollection)
        ->and($customer->user_type)->toBe(UserType::CUSTOMER)
        ->and($customer->phone)->toBe(Phone::toE164('0412000111'))
        ->and($this->inventory->fresh()->reserved_quantity)->toBe(40);

    $inquiry->refresh();

    expect($inquiry->status)->toBe(WholesaleInquiryStatus::Converted)
        ->and($inquiry->order_id)->toBe($order->id)
        ->and($inquiry->customer_id)->toBe($customer->id)
        ->and($inquiry->reviewed_by)->toBe($this->admin->id);
});

test('placing a wholesale order sends the delivery code and notifies branch staff', function () {
    $branchAdmin = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();

    $this->actingAs($this->admin, 'platform')
        ->post(route('platform.wholesale-orders.store'), wholesaleOrderPayload())
        ->assertSessionHasNoErrors();

    $order = Order::query()->firstOrFail();
    $notification = UserNotification::query()
        ->where('user_id', $branchAdmin->id)
        ->with('notification')
        ->latest('id')
        ->first();

    expect(app(FakeSmsSender::class)->lastCode())->not->toBeNull()
        ->and($notification)->not->toBeNull()
        ->and($notification->notification->title)->toBe('New wholesale order')
        ->and($notification->notification->data['link_url'])
        ->toBe(route('branch.orders.show', [$this->sydney, $order->fulfillments()->first()]));
});

test('a failed delivery code sms does not fail a placed wholesale order', function () {
    Exceptions::fake();
    $this->app->instance(SendsSms::class, new class implements SendsSms
    {
        public function send(string $toE164, string $message): void
        {
            throw new RuntimeException('SMS gateway is down');
        }
    });
    $branchAdmin = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();

    $this->actingAs($this->admin, 'platform')
        ->post(route('platform.wholesale-orders.store'), wholesaleOrderPayload())
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('platform.orders.show', Order::query()->firstOrFail()));

    Exceptions::assertReported(fn (RuntimeException $exception): bool => $exception->getMessage() === 'SMS gateway is down');

    expect(Order::query()->count())->toBe(1)
        ->and(UserNotification::query()
            ->where('user_id', $branchAdmin->id)
            ->whereHas('notification', fn ($query) => $query->where('title', 'New wholesale order'))
            ->exists())->toBeTrue();
});

test('wholesale order submission is idempotent', function () {
    $payload = wholesaleOrderPayload();

    $this->actingAs($this->admin, 'platform')
        ->post(route('platform.wholesale-orders.store'), $payload)
        ->assertSessionHasNoErrors();

    $this->actingAs($this->admin, 'platform')
        ->post(route('platform.wholesale-orders.store'), $payload)
        ->assertSessionHasNoErrors();

    expect(Order::query()->count())->toBe(1)
        ->and($this->inventory->fresh()->reserved_quantity)->toBe(40);
});

test('wholesale order is refused when the branch is short on stock', function () {
    $inquiry = WholesaleInquiry::factory()->create();

    $this->actingAs($this->admin, 'platform')
        ->post(route('platform.wholesale-orders.store'), wholesaleOrderPayload([
            'wholesale_inquiry_id' => $inquiry->id,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 101, 'unit_price' => 20],
            ],
        ]))
        ->assertSessionHasErrors('items.0.quantity');

    expect(Order::query()->count())->toBe(0)
        ->and($this->inventory->fresh()->reserved_quantity)->toBe(0)
        ->and($inquiry->fresh()->status)->toBe(WholesaleInquiryStatus::New);
});

test('the customer is notified with a link to track the wholesale order', function () {
    $this->actingAs($this->admin, 'platform')
        ->post(route('platform.wholesale-orders.store'), wholesaleOrderPayload())
        ->assertSessionHasNoErrors();

    $order = Order::query()->firstOrFail();
    $notification = UserNotification::query()
        ->where('user_id', $order->customer_id)
        ->whereHas('notification', fn ($query) => $query->where('title', 'Order placed'))
        ->with('notification')
        ->firstOrFail();

    expect($notification->notification->data['link_url'])->toBe(route('customer.orders.show', $order));
});

test('place order page lists branch variant stock alongside base products', function () {
    $variant = ProductVariant::factory()->create(['product_id' => $this->product->id, 'name' => 'Mint', 'status' => true]);
    Inventory::factory()->create([
        'branch_id' => $this->sydney->id,
        'product_id' => $this->product->id,
        'variant_id' => $variant->id,
        'quantity' => 12,
        'reserved_quantity' => 2,
        'status' => true,
    ]);

    $this->actingAs($this->admin, 'platform')
        ->get(route('platform.wholesale-inquiries.place-order.create', ['branch' => $this->sydney->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('products', 2)
            ->where('products', fn ($products) => collect($products)->contains(
                fn (array $row): bool => $row['id'] === $this->product->id.':'.$variant->id
                    && $row['variant_id'] === $variant->id
                    && $row['name'] === 'Wholesale Carton · Mint'
                    && $row['stock'] === 10,
            )));
});

test('place order page prices variants from their own branch row, else the product row', function () {
    config(['catalog.show_branch_pricing' => true]);

    $priced = ProductVariant::factory()->create(['product_id' => $this->product->id, 'name' => 'Mint', 'status' => true]);
    $fallback = ProductVariant::factory()->create(['product_id' => $this->product->id, 'name' => 'Berry', 'status' => true]);

    foreach ([$priced, $fallback] as $variant) {
        Inventory::factory()->create([
            'branch_id' => $this->sydney->id,
            'product_id' => $this->product->id,
            'variant_id' => $variant->id,
            'quantity' => 10,
            'reserved_quantity' => 0,
            'status' => true,
        ]);
    }

    BranchProductPrice::factory()->create([
        'branch_id' => $this->sydney->id,
        'product_id' => $this->product->id,
        'variant_id' => null,
        'wholesale_price' => 18,
    ]);
    BranchProductPrice::factory()->create([
        'branch_id' => $this->sydney->id,
        'product_id' => $this->product->id,
        'variant_id' => $priced->id,
        'wholesale_price' => 15,
    ]);

    $response = $this->actingAs($this->admin, 'platform')
        ->get(route('platform.wholesale-inquiries.place-order.create', ['branch' => $this->sydney->id]))
        ->assertOk();

    $prices = collect($response->inertiaProps('products'))->pluck('price', 'id');

    expect($prices[(string) $this->product->id])->toEqual(18.0)
        ->and($prices[$this->product->id.':'.$priced->id])->toEqual(15.0)
        ->and($prices[$this->product->id.':'.$fallback->id])->toEqual(18.0);
});

test('wholesale order reserves variant stock and snapshots the variant name', function () {
    $variant = ProductVariant::factory()->create(['product_id' => $this->product->id, 'name' => 'Mint', 'sku' => 'WC-MINT', 'status' => true]);
    $variantStock = Inventory::factory()->create([
        'branch_id' => $this->sydney->id,
        'product_id' => $this->product->id,
        'variant_id' => $variant->id,
        'quantity' => 30,
        'reserved_quantity' => 0,
        'status' => true,
    ]);

    $this->actingAs($this->admin, 'platform')
        ->post(route('platform.wholesale-orders.store'), wholesaleOrderPayload([
            'items' => [
                ['product_id' => $this->product->id, 'variant_id' => $variant->id, 'quantity' => 25, 'unit_price' => 20],
            ],
        ]))
        ->assertSessionHasNoErrors();

    $item = Order::query()->firstOrFail()->items()->firstOrFail();

    expect($item->variant_id)->toBe($variant->id)
        ->and($item->product_name_snapshot)->toBe('Wholesale Carton · Mint')
        ->and($item->sku_snapshot)->toBe('WC-MINT')
        ->and($variantStock->fresh()->reserved_quantity)->toBe(25)
        ->and($this->inventory->fresh()->reserved_quantity)->toBe(0);
});

test('wholesale order rejects a variant that belongs to another product', function () {
    $foreign = ProductVariant::factory()->create(['status' => true]);

    $this->actingAs($this->admin, 'platform')
        ->post(route('platform.wholesale-orders.store'), wholesaleOrderPayload([
            'items' => [
                ['product_id' => $this->product->id, 'variant_id' => $foreign->id, 'quantity' => 1, 'unit_price' => 20],
            ],
        ]))
        ->assertSessionHasErrors('items.0.product_id');

    expect(Order::query()->count())->toBe(0);
});

test('wholesale order is refused for a product the branch does not stock', function () {
    $unstocked = Product::factory()->create(['status' => true]);

    $this->actingAs($this->admin, 'platform')
        ->post(route('platform.wholesale-orders.store'), wholesaleOrderPayload([
            'items' => [
                ['product_id' => $unstocked->id, 'quantity' => 1, 'unit_price' => 5],
            ],
        ]))
        ->assertSessionHasErrors('items.0.quantity');

    expect(Order::query()->count())->toBe(0);
});

test('closed inquiries cannot be converted into an order', function () {
    $inquiry = WholesaleInquiry::factory()->rejected()->create();

    $this->actingAs($this->admin, 'platform')
        ->post(route('platform.wholesale-orders.store'), wholesaleOrderPayload([
            'wholesale_inquiry_id' => $inquiry->id,
        ]))
        ->assertSessionHasErrors('wholesale_inquiry_id');

    expect(Order::query()->count())->toBe(0);
});

test('wholesale order validates required fields', function () {
    $this->actingAs($this->admin, 'platform')
        ->post(route('platform.wholesale-orders.store'), [])
        ->assertSessionHasErrors(['name', 'phone', 'address', 'latitude', 'longitude', 'branch_id', 'items', 'payment_method', 'idempotency_key']);
});

test('linked customer accounts are reused and the discount never exceeds the subtotal', function () {
    $customer = User::query()->where('email', UserSeeder::CUSTOMER)->firstOrFail();
    $inquiry = WholesaleInquiry::factory()->create(['customer_id' => $customer->id]);

    $this->actingAs($this->admin, 'platform')
        ->post(route('platform.wholesale-orders.store'), wholesaleOrderPayload([
            'wholesale_inquiry_id' => $inquiry->id,
            'discount' => 5000,
            'delivery_fee' => 0,
        ]))
        ->assertSessionHasNoErrors();

    $order = Order::query()->firstOrFail();

    expect($order->customer_id)->toBe($customer->id)
        ->and((float) $order->discount)->toBe(800.0)
        ->and((float) $order->total_amount)->toBe(0.0);
});

test('order numbers stay sequential across checkout and wholesale orders', function () {
    $year = now()->format('Y');
    Order::factory()->create(['order_number' => "PMM-{$year}-0007"]);

    $this->actingAs($this->admin, 'platform')
        ->post(route('platform.wholesale-orders.store'), wholesaleOrderPayload())
        ->assertSessionHasNoErrors();

    expect(Order::query()->latest('id')->value('order_number'))->toBe("PMM-{$year}-0008");
});

test('delivery quote endpoint returns distance and fee for a branch', function () {
    $this->actingAs($this->admin, 'platform')
        ->getJson(route('platform.wholesale-orders.delivery-quote', [
            'branch' => $this->sydney->id,
            'latitude' => -33.8650,
            'longitude' => 151.2094,
        ]))
        ->assertOk()
        ->assertJsonStructure(['distance_km', 'fee']);
});

test('wholesale orders surface their source across platform, branch and customer portals', function () {
    $customer = User::query()->where('email', UserSeeder::CUSTOMER)->firstOrFail();
    $inquiry = WholesaleInquiry::factory()->create(['customer_id' => $customer->id]);
    $branchAdmin = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();

    $this->actingAs($this->admin, 'platform')
        ->post(route('platform.wholesale-orders.store'), wholesaleOrderPayload([
            'wholesale_inquiry_id' => $inquiry->id,
        ]))
        ->assertSessionHasNoErrors();

    $order = Order::query()->firstOrFail();
    $fulfillment = $order->fulfillments()->firstOrFail();
    $checkout = Order::factory()->create(['source' => OrderSource::CustomerCheckout]);
    OrderFulfillment::factory()->forOrder($checkout)->create(['branch_id' => $this->sydney->id]);

    $this->actingAs($this->admin, 'platform')
        ->get(route('platform.orders.show', $order))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('order.source', 'wholesale')
            ->where('order.wholesaleInquiry.id', $inquiry->id)
            ->where('order.wholesaleInquiry.url', route('platform.wholesale-inquiries.show', $inquiry)));

    $this->actingAs($branchAdmin, 'branch')
        ->get(route('branch.orders.index', ['branch' => $this->sydney, 'filter' => ['source' => 'wholesale']]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('table.data', 1)
            ->where('table.data.0.id', $fulfillment->id)
            ->has('table.filterOptions.source'));

    $this->actingAs($branchAdmin, 'branch')
        ->get(route('branch.orders.show', [$this->sydney, $fulfillment]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('fulfillment.order.source', 'wholesale')
            ->where('fulfillment.order.shippingAddress.company', 'Harbour Traders Pty Ltd'));

    $this->actingAs($customer, 'customer')
        ->get(route('customer.orders.show', $order))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('order.source', 'wholesale')
            ->where('order.wholesaleRequestId', $inquiry->id));

    $this->actingAs($customer, 'customer')
        ->get(route('customer.orders.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('orders.0.source', 'wholesale'));
});

test('a wholesale order flows from branch to driver and is delivered with the code', function () {
    $branchAdmin = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $driver = User::query()->where('email', UserSeeder::DRIVER)->firstOrFail();
    scheduleDriverOnDuty($driver, $this->sydney);

    $this->actingAs($this->admin, 'platform')
        ->post(route('platform.wholesale-orders.store'), wholesaleOrderPayload())
        ->assertSessionHasNoErrors();

    $order = Order::query()->firstOrFail();
    $fulfillment = $order->fulfillments()->firstOrFail();
    $code = app(FakeSmsSender::class)->lastCode();

    $statuses = app(FulfillmentStatusService::class);
    $statuses->transition($fulfillment, FulfillmentStatus::Processing, $branchAdmin);
    $statuses->transition($fulfillment->refresh(), FulfillmentStatus::Ready, $branchAdmin);
    app(DriverAssignmentService::class)->assign($fulfillment->refresh(), $driver, $branchAdmin);

    $this->actingAs($driver, 'driver')
        ->get(route('driver.orders.show', $order))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('order.source', 'wholesale')
            ->where('order.company', 'Harbour Traders Pty Ltd'));

    $deliveries = app(DriverDeliveryService::class);
    $deliveries->pickUp($fulfillment->refresh(), $driver);
    $deliveries->startDelivery($fulfillment->refresh(), $driver);
    $deliveries->arrive($fulfillment->refresh(), $driver);
    $deliveries->complete($fulfillment->refresh(), $driver, (string) $code);

    $inventory = $this->inventory->fresh();

    expect($fulfillment->refresh()->status)->toBe(FulfillmentStatus::Delivered)
        ->and($order->refresh()->status)->toBe(OrderStatus::Delivered)
        ->and($order->payment->status)->toBe(PaymentStatus::Collected)
        ->and($inventory->quantity)->toBe(60)
        ->and($inventory->reserved_quantity)->toBe(0);
});
