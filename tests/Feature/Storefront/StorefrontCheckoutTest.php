<?php

use App\Contracts\SendsSms;
use App\Enums\CartStatus;
use App\Enums\CouponType;
use App\Enums\CustomerRegistrationStatus;
use App\Enums\DriverAssignmentStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserType;
use App\Models\Address;
use App\Models\AllocationDecisionLog;
use App\Models\Branch;
use App\Models\BranchArea;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\DeliveryShift;
use App\Models\DriverBranchAssignment;
use App\Models\DriverProfile;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentGatewaySetting;
use App\Models\Product;
use App\Models\User;
use App\Services\Application\ApplicationSettingsService;
use App\Services\Application\PaymentGatewaySettingsService;
use App\Services\Catalog\DeliverySettingsService;
use App\Services\Order\OrderPlacementNotifier;
use App\Services\Sms\FakeSmsSender;
use App\Support\Delivery\DeliveryShiftAvailability;
use App\Support\Geo\Polygon;
use App\Support\Phone;
use Database\Seeders\ApplicationSettingsSeeder;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(ApplicationSettingsSeeder::class);
    app(PaymentGatewaySettingsService::class)->ensureDefaults();
    app(DeliverySettingsService::class)->ensureDefaults();
    seedCheckoutDeliveryShifts();
    Carbon\Carbon::setTestNow(Carbon\Carbon::parse('2026-11-09 10:00:00', config('app.timezone')));
});

afterEach(function () {
    Carbon\Carbon::setTestNow();
});

function storefrontCheckoutProduct(array $overrides = []): Product
{
    return Product::factory()->create(array_merge([
        'selling_price' => 60,
        'regular_price' => 70,
        'status' => true,
    ], $overrides));
}

function storefrontCheckoutBranchWithStock(Product $product, int $quantity = 10): Branch
{
    $branch = Branch::factory()->create([
        'latitude' => -33.8688,
        'longitude' => 151.2093,
        'delivery_radius_km' => 50,
        'status' => true,
    ]);

    BranchArea::factory()->forBranch($branch)->create([
        'latitude' => -33.8688,
        'longitude' => 151.2093,
        'polygon' => Polygon::squareAround(-33.8688, 151.2093, 0.05),
        'is_active' => true,
    ]);

    Inventory::factory()->create([
        'branch_id' => $branch->id,
        'product_id' => $product->id,
        'variant_id' => null,
        'quantity' => $quantity,
        'status' => true,
    ]);

    return $branch;
}

function checkoutPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Ada Lovelace',
        'mobile' => '0410123456',
        'address' => '1 Test Street',
        'city' => 'Sydney',
        'postal_code' => '2000',
        'latitude' => -33.8688,
        'longitude' => 151.2093,
        'delivery_method' => 'regular',
        'time_slot_id' => checkoutDeliveryShiftId(),
        'payment_method' => PaymentGatewaySetting::KEY_COD,
        'terms' => true,
    ], $overrides);
}

test('checkout props omit branch keys', function () {
    $user = User::factory()->create();
    $product = storefrontCheckoutProduct(['slug' => 'checkout-branch-free']);

    $this->actingAs($user, 'customer')
        ->post(route('cart.store'), [
            'product' => $product->slug,
            'quantity' => 1,
        ]);

    $this->actingAs($user, 'customer')
        ->get(route('checkout'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/checkout')
            ->missing('groups')
            ->missing('branches')
            ->has('items.0', fn (Assert $item) => $item
                ->has('id')
                ->has('name')
                ->missing('branchId')
                ->missing('branchName')
                ->etc())
            ->has('paymentMethods', 2)
            ->where('paymentMethods.0.id', PaymentGatewaySetting::KEY_COD)
            ->where('paymentMethods.1.id', PaymentGatewaySetting::KEY_POS)
            ->has('deliveryMethods', 2)
            ->where('deliveryMethods.0.id', 'regular')
            ->where('deliveryMethods.0.label', 'Same day delivery')
            ->where('deliveryMethods.0.requiresSlot', true)
            ->where('deliveryMethods.0.requiresDate', false)
            ->where('deliveryMethods.1.id', 'express')
            ->where('deliveryMethods.1.label', 'Late delivery')
            ->where('deliveryMethods.1.requiresSlot', true)
            ->where('deliveryMethods.1.requiresDate', true)
            ->where('deliveryMethods.1.minDate', '2026-11-10')
            ->where('deliveryMethods.1.maxDate', '2026-11-23')
            ->has('deliveryMethods.0.timeSlots', 2)
            ->has('deliveryMethods.1.timeSlots', 3)
            ->missing('timeSlots')
            ->missing('deliveryMethods.0.formattedFee')
            ->missing('deliveryMethods.1.formattedFee')
            ->where('shippingQuote', null));
});

test('checkout shares the application settings name', function () {
    app(ApplicationSettingsService::class)->update([
        'app_name' => 'Harbour Smoke',
        'app_short_name' => 'Harbour',
        'date_format' => 'Y-m-d',
        'time_format' => 'H:i',
        'theme_color' => '#174B36',
        'background_color' => '#FFFFFF',
    ]);

    $user = User::factory()->create();
    $product = storefrontCheckoutProduct(['slug' => 'branded-checkout-item']);

    $this->actingAs($user, 'customer')
        ->post(route('cart.store'), [
            'product' => $product->slug,
            'quantity' => 1,
        ]);

    $this->actingAs($user, 'customer')
        ->get(route('checkout'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('name', 'Harbour Smoke')
            ->where('application.name', 'Harbour Smoke'));
});

test('platform branch and driver staff can open storefront checkout', function (string $guard, UserType $type) {
    $product = storefrontCheckoutProduct(['slug' => "staff-open-{$guard}"]);

    $user = User::factory()->create([
        'user_type' => $type,
        'branch_id' => $type === UserType::BRANCH
            ? Branch::factory()->create()->id
            : null,
    ]);

    $this->actingAs($user, $guard)
        ->post(route('cart.store'), [
            'product' => $product->slug,
            'quantity' => 1,
        ]);

    $this->actingAs($user, $guard)
        ->get(route('checkout'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/checkout')
            ->has('items', 1)
            ->where('savedAddress', null));
})->with([
    'platform' => ['platform', UserType::PLATFORM],
    'branch' => ['branch', UserType::BRANCH],
    'driver' => ['driver', UserType::DRIVER],
]);

test('platform branch and driver staff place a storefront order for the customer who owns the phone', function (string $guard, UserType $type) {
    $product = storefrontCheckoutProduct(['slug' => "staff-order-{$guard}"]);
    storefrontCheckoutBranchWithStock($product);

    $user = User::factory()->create([
        'user_type' => $type,
        'branch_id' => $type === UserType::BRANCH
            ? Branch::factory()->create()->id
            : null,
    ]);

    $this->actingAs($user, $guard)
        ->post(route('cart.store'), [
            'product' => $product->slug,
            'quantity' => 1,
        ]);

    $this->actingAs($user, $guard)
        ->post(route('checkout.store'), checkoutPayload(['save_as_default' => true]))
        ->assertSessionDoesntHaveErrors();

    $order = Order::query()->firstOrFail();
    $customer = User::query()->findOrFail($order->customer_id);

    expect($customer->id)->not->toBe($user->id)
        ->and($customer->phone)->toBe(Phone::toE164('0410123456'))
        ->and($customer->user_type)->toBe(UserType::CUSTOMER)
        ->and($customer->registration_status)->toBe(CustomerRegistrationStatus::Provisional)
        ->and(Address::query()->count())->toBe(0);

    $this->actingAs($user, $guard)
        ->get(route('checkout.confirmation', $order))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/checkout-confirmation')
            ->where('order.viewOrderUrl', fn ($url): bool => str_contains((string) $url, '/order-status/')));
})->with([
    'platform' => ['platform', UserType::PLATFORM],
    'branch' => ['branch', UserType::BRANCH],
    'driver' => ['driver', UserType::DRIVER],
]);

test('a logged-in staff user ordering with their own phone gets the order on their own account', function () {
    $product = storefrontCheckoutProduct(['slug' => 'staff-own-phone-item']);
    storefrontCheckoutBranchWithStock($product);

    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'phone' => Phone::toE164('0410123456'),
    ]);
    $usersBefore = User::query()->count();

    $this->actingAs($driver, 'driver')
        ->post(route('cart.store'), ['product' => $product->slug, 'quantity' => 1]);

    $this->actingAs($driver, 'driver')
        ->post(route('checkout.store'), checkoutPayload())
        ->assertSessionDoesntHaveErrors();

    expect(Order::query()->firstOrFail()->customer_id)->toBe($driver->id)
        ->and(User::query()->count())->toBe($usersBefore);
});

test('checkout rejects a phone that is not an Australian mobile number', function (bool $loggedIn, string $mobile) {
    $product = storefrontCheckoutProduct(['slug' => 'non-au-phone-item-'.md5($loggedIn.$mobile)]);
    storefrontCheckoutBranchWithStock($product);

    if ($loggedIn) {
        $this->actingAs(User::factory()->create(), 'customer');
    }

    $this->post(route('cart.store'), ['product' => $product->slug, 'quantity' => 1]);

    $this->post(route('checkout.store'), checkoutPayload(['mobile' => $mobile]))
        ->assertSessionHasErrors(['mobile' => 'Enter a valid Australian mobile number.']);

    expect(Order::query()->count())->toBe(0);
})->with([
    'guest, bangladesh mobile' => [false, '+8801712345678'],
    'guest, us number' => [false, '+12025550123'],
    'guest, australian landline' => [false, '0291234567'],
    'customer, bangladesh mobile' => [true, '+8801712345678'],
    'customer, us number' => [true, '+12025550123'],
    'customer, australian landline' => [true, '0291234567'],
    'customer, not a number' => [true, 'call me maybe'],
]);

test('checkout accepts an Australian mobile number in international format', function () {
    $product = storefrontCheckoutProduct(['slug' => 'au-e164-phone-item']);
    storefrontCheckoutBranchWithStock($product);

    $this->post(route('cart.store'), ['product' => $product->slug, 'quantity' => 1]);

    $this->post(route('checkout.store'), checkoutPayload(['mobile' => '+61410123456']))
        ->assertSessionDoesntHaveErrors();

    expect(Order::query()->count())->toBe(1);
});

test('checkout without a delivery shift is rejected', function () {
    $user = User::factory()->create();
    $product = storefrontCheckoutProduct(['slug' => 'express-needs-slot']);
    storefrontCheckoutBranchWithStock($product);

    $this->actingAs($user, 'customer')
        ->post(route('cart.store'), [
            'product' => $product->slug,
            'quantity' => 1,
        ]);

    $this->actingAs($user, 'customer')
        ->from(route('checkout'))
        ->post(route('checkout.store'), checkoutPayload([
            'delivery_method' => 'express',
            'delivery_date' => '2026-11-10',
            'time_slot_id' => '',
        ]))
        ->assertRedirect(route('checkout'))
        ->assertSessionHasErrors('time_slot_id');

    expect(Order::query()->count())->toBe(0);
});

test('same-day checkout stores today and the chosen delivery shift', function () {
    $user = User::factory()->create();
    $product = storefrontCheckoutProduct(['slug' => 'regular-with-shift']);
    storefrontCheckoutBranchWithStock($product);
    $shiftId = checkoutDeliveryShiftId();

    $this->actingAs($user, 'customer')
        ->post(route('cart.store'), [
            'product' => $product->slug,
            'quantity' => 1,
        ]);

    $this->actingAs($user, 'customer')
        ->post(route('checkout.store'), checkoutPayload([
            'delivery_method' => 'regular',
            'delivery_date' => null,
            'time_slot_id' => $shiftId,
        ]))
        ->assertRedirect();

    $order = Order::query()->firstOrFail();

    expect($order->delivery_method)->toBe('regular')
        ->and($order->delivery_date?->toDateString())->toBe('2026-11-09')
        ->and($order->delivery_shift_id)->toBe($shiftId)
        ->and($order->time_slot)->not->toBeNull();
});

test('same-day checkout rejects a shift that has already started', function () {
    Carbon\Carbon::setTestNow(Carbon\Carbon::parse('2026-11-09 14:11:00', config('app.timezone')));

    $user = User::factory()->create();
    $product = storefrontCheckoutProduct(['slug' => 'regular-past-shift']);
    storefrontCheckoutBranchWithStock($product);
    $morning = DeliveryShift::query()->where('name', 'Morning Delivery')->firstOrFail();

    $this->actingAs($user, 'customer')
        ->post(route('cart.store'), [
            'product' => $product->slug,
            'quantity' => 1,
        ]);

    $this->actingAs($user, 'customer')
        ->get(route('checkout'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/checkout')
            ->has('deliveryMethods.0.timeSlots', 1)
            ->where('deliveryMethods.0.id', 'regular')
            ->where('deliveryMethods.0.timeSlots.0.name', 'Evening Delivery')
            ->where('deliveryMethods.1.requiresDate', true)
            ->where('deliveryMethods.1.minDate', '2026-11-10')
            ->where('deliveryMethods.1.maxDate', '2026-11-23'));

    $this->actingAs($user, 'customer')
        ->from(route('checkout'))
        ->post(route('checkout.store'), checkoutPayload([
            'delivery_method' => 'regular',
            'time_slot_id' => $morning->id,
        ]))
        ->assertRedirect(route('checkout'))
        ->assertSessionHasErrors('time_slot_id');
});

test('late delivery requires a future date within fourteen days', function () {
    $user = User::factory()->create();
    $product = storefrontCheckoutProduct(['slug' => 'express-with-shift']);
    storefrontCheckoutBranchWithStock($product);
    $shiftId = checkoutDeliveryShiftId();

    $this->actingAs($user, 'customer')
        ->post(route('cart.store'), [
            'product' => $product->slug,
            'quantity' => 1,
        ]);

    $this->actingAs($user, 'customer')
        ->from(route('checkout'))
        ->post(route('checkout.store'), checkoutPayload([
            'delivery_method' => 'express',
            'delivery_date' => '2026-11-09',
            'time_slot_id' => $shiftId,
        ]))
        ->assertRedirect(route('checkout'))
        ->assertSessionHasErrors('delivery_date');

    $this->actingAs($user, 'customer')
        ->from(route('checkout'))
        ->post(route('checkout.store'), checkoutPayload([
            'delivery_method' => 'express',
            'delivery_date' => '2026-11-24',
            'time_slot_id' => $shiftId,
        ]))
        ->assertRedirect(route('checkout'))
        ->assertSessionHasErrors('delivery_date');

    $this->actingAs($user, 'customer')
        ->post(route('checkout.store'), checkoutPayload([
            'delivery_method' => 'express',
            'delivery_date' => '2026-11-23',
            'time_slot_id' => $shiftId,
        ]))
        ->assertRedirect();

    $order = Order::query()->firstOrFail();

    expect($order->delivery_method)->toBe('express')
        ->and($order->delivery_date?->toDateString())->toBe('2026-11-23')
        ->and($order->delivery_shift_id)->toBe($shiftId);
});

test('same-day method is omitted when no eligible shifts remain', function () {
    Carbon\Carbon::setTestNow(Carbon\Carbon::parse('2026-11-09 22:00:00', config('app.timezone')));

    $user = User::factory()->create();
    $product = storefrontCheckoutProduct(['slug' => 'no-same-day-left']);
    storefrontCheckoutBranchWithStock($product);

    $this->actingAs($user, 'customer')
        ->post(route('cart.store'), [
            'product' => $product->slug,
            'quantity' => 1,
        ]);

    $this->actingAs($user, 'customer')
        ->get(route('checkout'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/checkout')
            ->has('deliveryMethods', 1)
            ->where('deliveryMethods.0.id', 'express'));
});

test('pinning a location quotes the distance-based shipping fee', function () {
    $user = User::factory()->create();
    $product = storefrontCheckoutProduct(['slug' => 'quoted-shipping-item']);
    storefrontCheckoutBranchWithStock($product);

    $this->actingAs($user, 'customer')
        ->post(route('cart.store'), [
            'product' => $product->slug,
            'quantity' => 1,
        ]);

    $this->actingAs($user, 'customer')
        ->from(route('checkout'))
        ->postJson(route('checkout.shipping'), [
            'latitude' => -33.8688,
            'longitude' => 151.2093,
        ])
        ->assertOk()
        ->assertJsonPath('formattedFee', fn (string $fee) => str_starts_with($fee, '$'))
        ->assertJsonPath('fee', fn (mixed $fee) => (float) $fee > 0)
        ->assertSessionHas('storefront.checkout_shipping');

    $quote = session('storefront.checkout_shipping');

    expect($quote['fee'])->toBeGreaterThan(0)
        ->and($quote['formattedFee'])->toStartWith('$');

    $this->actingAs($user, 'customer')
        ->get(route('checkout'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('shippingQuote.formattedFee', $quote['formattedFee'])
            ->where('shippingQuote.fee', $quote['fee']));
});

test('a shipping quote without coordinates is rejected as json', function () {
    $user = User::factory()->create();
    $product = storefrontCheckoutProduct(['slug' => 'quote-needs-pin']);

    $this->actingAs($user, 'customer')
        ->post(route('cart.store'), [
            'product' => $product->slug,
            'quantity' => 1,
        ]);

    $this->actingAs($user, 'customer')
        ->postJson(route('checkout.shipping'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['latitude', 'longitude']);
});

test('place order writes order payment and redemption then clears ordered lines', function () {
    $user = User::factory()->create();
    $product = storefrontCheckoutProduct(['slug' => 'place-order-item']);
    storefrontCheckoutBranchWithStock($product);
    $coupon = Coupon::factory()->create([
        'code' => 'SAVE10',
        'type' => CouponType::Fixed,
        'value' => 10,
        'min_subtotal' => 50,
        'status' => true,
    ]);

    $this->actingAs($user, 'customer')
        ->post(route('cart.store'), [
            'product' => $product->slug,
            'quantity' => 1,
        ]);

    $response = $this->actingAs($user, 'customer')
        ->post(route('checkout.store'), checkoutPayload([
            'coupon' => $coupon->code,
        ]));

    $order = Order::query()->firstOrFail();

    $response->assertRedirect(route('checkout.confirmation', $order));

    expect(parse_url((string) $response->headers->get('Location'), PHP_URL_PATH))
        ->toBe('/checkout/confirmation/'.$order->order_number);

    expect($order->customer_id)->toBe($user->id)
        ->and($order->items)->toHaveCount(1)
        ->and((float) $order->discount)->toBe(10.0)
        ->and($order->payment_method)->toBe(PaymentGatewaySetting::KEY_COD)
        ->and($order->source)->toBe(OrderSource::CustomerCheckout)
        ->and($order->placed_by)->toBeNull()
        ->and($order->fulfillments)->toHaveCount(1)
        ->and($order->shipping_address_snapshot['name'])->toBe('Ada Lovelace')
        ->and($order->address_id)->toBeNull();

    $payment = Payment::query()->where('order_id', $order->id)->firstOrFail();

    expect($payment->status)->toBe(PaymentStatus::PendingCollection)
        ->and($payment->gateway)->toBe(PaymentGatewaySetting::KEY_COD);

    expect(CouponRedemption::query()->where('order_id', $order->id)->count())->toBe(1);

    expect(
        CartItem::query()
            ->whereHas('cart', fn ($query) => $query->where('user_id', $user->id))
            ->count()
    )->toBe(0);

    expect(
        Cart::query()
            ->where('user_id', $user->id)
            ->where('status', CartStatus::Converted)
            ->exists()
    )->toBeTrue();

    $this->actingAs($user, 'customer')
        ->get(route('checkout.confirmation', $order))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/checkout-confirmation')
            ->where('order.orderNumber', $order->order_number)
            ->where('order.name', 'Ada Lovelace')
            ->missing('order.id'));
});

test('checkout confirmation uses the order number not the database id', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create([
        'customer_id' => $user->id,
        'order_number' => 'PMM-2026-9999',
    ]);

    $this->actingAs($user, 'customer')
        ->get('/checkout/confirmation/'.$order->id)
        ->assertNotFound();

    $this->actingAs($user, 'customer')
        ->get(route('checkout.confirmation', $order))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/checkout-confirmation')
            ->where('order.orderNumber', 'PMM-2026-9999')
            ->missing('order.id'));
});

test('a failed place order leaves the cart intact', function () {
    $user = User::factory()->create();
    $product = storefrontCheckoutProduct(['slug' => 'failed-place-order']);
    storefrontCheckoutBranchWithStock($product);

    $this->actingAs($user, 'customer')
        ->post(route('cart.store'), [
            'product' => $product->slug,
            'quantity' => 2,
        ]);

    $this->actingAs($user, 'customer')
        ->from(route('checkout'))
        ->post(route('checkout.store'), checkoutPayload([
            'name' => '',
        ]))
        ->assertRedirect(route('checkout'))
        ->assertSessionHasErrors('name');

    expect(Order::query()->count())->toBe(0)
        ->and(CartItem::query()->sum('quantity'))->toBe(2);
});

test('invalid expired and min-not-met coupons are rejected', function () {
    $user = User::factory()->create();
    $product = storefrontCheckoutProduct(['slug' => 'coupon-rules-item', 'selling_price' => 20]);

    $this->actingAs($user, 'customer')
        ->post(route('cart.store'), [
            'product' => $product->slug,
            'quantity' => 1,
        ]);

    $this->actingAs($user, 'customer')
        ->from(route('checkout'))
        ->post(route('checkout.coupon'), ['coupon' => 'NOPE'])
        ->assertRedirect(route('checkout'))
        ->assertSessionHasErrors('coupon');

    Coupon::factory()->create([
        'code' => 'OLDONE',
        'starts_at' => now()->subMonth(),
        'ends_at' => now()->subDay(),
        'status' => true,
    ]);

    $this->actingAs($user, 'customer')
        ->from(route('checkout'))
        ->post(route('checkout.coupon'), ['coupon' => 'OLDONE'])
        ->assertRedirect(route('checkout'))
        ->assertSessionHasErrors('coupon');

    Coupon::factory()->create([
        'code' => 'MIN100',
        'min_subtotal' => 100,
        'status' => true,
    ]);

    $this->actingAs($user, 'customer')
        ->from(route('checkout'))
        ->post(route('checkout.coupon'), ['coupon' => 'MIN100'])
        ->assertRedirect(route('checkout'))
        ->assertSessionHasErrors('coupon');
});

test('applying a coupon shares appliedCoupon on the checkout page', function () {
    $user = User::factory()->create();
    $product = storefrontCheckoutProduct(['slug' => 'apply-coupon-inertia', 'selling_price' => 60]);
    Coupon::factory()->create([
        'code' => 'SAVE10',
        'type' => CouponType::Fixed,
        'value' => 10,
        'min_subtotal' => 50,
        'status' => true,
    ]);

    $this->actingAs($user, 'customer')
        ->post(route('cart.store'), [
            'product' => $product->slug,
            'quantity' => 1,
        ]);

    $this->actingAs($user, 'customer')
        ->from(route('checkout'))
        ->post(route('checkout.coupon'), ['coupon' => 'SAVE10'])
        ->assertRedirect(route('checkout'))
        ->assertSessionHas('storefront.checkout_coupon');

    $this->actingAs($user, 'customer')
        ->get(route('checkout'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/checkout')
            ->where('appliedCoupon.code', 'SAVE10')
            ->where('appliedCoupon.discount', 10));
});

test('checkout clears a session coupon when the cart no longer meets the minimum', function () {
    $user = User::factory()->create();
    $product = storefrontCheckoutProduct(['slug' => 'coupon-requote-item', 'selling_price' => 60]);
    Coupon::factory()->create([
        'code' => 'MIN100',
        'type' => CouponType::Fixed,
        'value' => 10,
        'min_subtotal' => 100,
        'status' => true,
    ]);

    $this->actingAs($user, 'customer')
        ->post(route('cart.store'), [
            'product' => $product->slug,
            'quantity' => 2,
        ]);

    $this->actingAs($user, 'customer')
        ->from(route('checkout'))
        ->post(route('checkout.coupon'), ['coupon' => 'MIN100'])
        ->assertRedirect(route('checkout'))
        ->assertSessionHas('storefront.checkout_coupon');

    $item = CartItem::query()->sole();

    $this->actingAs($user, 'customer')
        ->patch(route('cart.update', $item), ['quantity' => 1])
        ->assertRedirect();

    $this->actingAs($user, 'customer')
        ->get(route('checkout'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/checkout')
            ->where('appliedCoupon', null))
        ->assertSessionMissing('storefront.checkout_coupon');
});

test('an applied coupon can be removed from checkout', function () {
    $user = User::factory()->create();
    $product = storefrontCheckoutProduct(['slug' => 'remove-coupon-item']);
    Coupon::factory()->create([
        'code' => 'SAVE10',
        'type' => CouponType::Fixed,
        'value' => 10,
        'min_subtotal' => 50,
        'status' => true,
    ]);

    $this->actingAs($user, 'customer')
        ->post(route('cart.store'), [
            'product' => $product->slug,
            'quantity' => 1,
        ]);

    $this->actingAs($user, 'customer')
        ->from(route('checkout'))
        ->post(route('checkout.coupon'), ['coupon' => 'SAVE10'])
        ->assertRedirect(route('checkout'))
        ->assertSessionHas('storefront.checkout_coupon');

    $this->actingAs($user, 'customer')
        ->from(route('checkout'))
        ->delete(route('checkout.coupon.destroy'))
        ->assertRedirect(route('checkout'))
        ->assertSessionMissing('storefront.checkout_coupon');
});

test('guests can remove a checkout coupon', function () {
    $this->withSession(['storefront.checkout_coupon' => ['code' => 'X']])
        ->delete(route('checkout.coupon.destroy'))
        ->assertRedirect()
        ->assertSessionMissing('storefront.checkout_coupon');
});

test('a guest can place an order through a provisional customer', function () {
    $product = storefrontCheckoutProduct(['slug' => 'guest-order-item']);
    storefrontCheckoutBranchWithStock($product);

    $this->post(route('cart.store'), ['product' => $product->slug, 'quantity' => 1]);

    $response = $this->post(route('checkout.store'), checkoutPayload([
        'save_as_default' => true,
    ]));

    $order = Order::query()->firstOrFail();
    $customer = User::query()->findOrFail($order->customer_id);

    $response->assertRedirect(route('checkout.confirmation', $order));
    expect($customer->registration_status)->toBe(CustomerRegistrationStatus::Provisional)
        ->and($customer->user_type)->toBe(UserType::CUSTOMER)
        ->and(Address::query()->count())->toBe(0);

    $this->get(route('checkout.confirmation', $order))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/checkout-confirmation')
            ->where('order.viewOrderUrl', fn ($url): bool => str_contains((string) $url, '/order-status/')));
});

test('a guest repeat order with the same phone reuses the provisional customer', function () {
    $product = storefrontCheckoutProduct(['slug' => 'guest-repeat-item']);
    storefrontCheckoutBranchWithStock($product);

    foreach (range(1, 2) as $ignored) {
        $this->post(route('cart.store'), ['product' => $product->slug, 'quantity' => 1]);
        $this->post(route('checkout.store'), checkoutPayload());
    }

    expect(Order::query()->count())->toBe(2)
        ->and(Order::query()->distinct()->pluck('customer_id'))->toHaveCount(1);
});

test('a guest confirmation page is hidden from other sessions', function () {
    $product = storefrontCheckoutProduct(['slug' => 'guest-private-item']);
    storefrontCheckoutBranchWithStock($product);

    $this->post(route('cart.store'), ['product' => $product->slug, 'quantity' => 1]);
    $this->post(route('checkout.store'), checkoutPayload());
    $order = Order::query()->firstOrFail();

    $this->flushSession();

    $this->get(route('checkout.confirmation', $order))->assertNotFound();
});

test('a guest can order with the phone of a registered account without logging in', function () {
    $product = storefrontCheckoutProduct(['slug' => 'guest-registered-item']);
    storefrontCheckoutBranchWithStock($product);

    $registered = User::factory()->create([
        'name' => 'Registered Owner',
        'email' => 'owner@example.com',
        'phone' => Phone::toE164('0410123456'),
        'registration_status' => CustomerRegistrationStatus::Registered,
    ]);
    $usersBefore = User::query()->count();

    $this->post(route('cart.store'), ['product' => $product->slug, 'quantity' => 1]);

    $this->post(route('checkout.store'), checkoutPayload([
        'name' => 'Someone At The Door',
        'save_as_default' => true,
    ]))->assertSessionDoesntHaveErrors();

    $order = Order::query()->firstOrFail();

    expect($order->customer_id)->toBe($registered->id)
        ->and($order->shipping_address_snapshot['name'])->toBe('Someone At The Door')
        ->and(User::query()->count())->toBe($usersBefore)
        ->and($registered->fresh()->name)->toBe('Registered Owner')
        ->and($registered->fresh()->email)->toBe('owner@example.com')
        ->and(Address::query()->count())->toBe(0);

    $this->get(route('checkout.confirmation', $order))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/checkout-confirmation')
            ->where('order.viewOrderUrl', fn ($url): bool => str_contains((string) $url, '/order-status/')));

    $this->flushSession();

    $this->get(route('checkout.confirmation', $order))->assertNotFound();
});

test('a guest order on a registered number opens pending until the account has a delivered order', function (bool $hasDeliveredOrder, FulfillmentStatus $expected) {
    $product = storefrontCheckoutProduct(['slug' => 'guest-registered-status-item']);
    storefrontCheckoutBranchWithStock($product);

    $registered = User::factory()->create([
        'phone' => Phone::toE164('0410123456'),
        'registration_status' => CustomerRegistrationStatus::Registered,
    ]);

    if ($hasDeliveredOrder) {
        Order::factory()->delivered()->create(['customer_id' => $registered->id]);
    }

    $this->post(route('cart.store'), ['product' => $product->slug, 'quantity' => 1]);
    $this->post(route('checkout.store'), checkoutPayload())->assertSessionDoesntHaveErrors();

    $order = Order::query()->where('customer_id', $registered->id)->latest('id')->firstOrFail();

    expect($order->fulfillment->status)->toBe($expected);
})->with([
    'no delivered order' => [false, FulfillmentStatus::Pending],
    'has a delivered order' => [true, FulfillmentStatus::Placed],
]);

test('placing a guest order texts the public view order link to the order phone', function () {
    $product = storefrontCheckoutProduct(['slug' => 'sms-link-guest-item']);
    storefrontCheckoutBranchWithStock($product);
    $sms = app(FakeSmsSender::class);
    $sms->reset();

    $this->post(route('cart.store'), ['product' => $product->slug, 'quantity' => 1]);
    $this->post(route('checkout.store'), checkoutPayload())->assertSessionDoesntHaveErrors();

    $order = Order::query()->firstOrFail();
    $messages = collect($sms->messages)->where('to', Phone::toE164('0410123456'));

    // A brand-new customer opens Pending: only the link SMS goes out, no delivery code yet.
    expect($messages)->toHaveCount(1)
        ->and($messages->first()['message'])->toContain($order->order_number)
        ->and($sms->lastCode())->toBeNull();

    preg_match('~https?://\S+~', $messages->first()['message'], $match);

    $this->flushSession();
    $this->get($match[0])
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/order-tracking')
            ->where('order.orderNumber', $order->order_number));
});

test('a logged-in customer also gets the view order link by sms, after the delivery code when trusted', function () {
    $user = User::factory()->create(['phone' => Phone::toE164('0410123456')]);
    Order::factory()->delivered()->create(['customer_id' => $user->id]);
    $product = storefrontCheckoutProduct(['slug' => 'sms-link-trusted-item']);
    storefrontCheckoutBranchWithStock($product);
    $sms = app(FakeSmsSender::class);
    $sms->reset();

    $this->actingAs($user, 'customer')
        ->post(route('cart.store'), ['product' => $product->slug, 'quantity' => 1]);
    $this->actingAs($user, 'customer')
        ->post(route('checkout.store'), checkoutPayload())
        ->assertSessionDoesntHaveErrors();

    $order = Order::query()->where('customer_id', $user->id)->latest('id')->firstOrFail();
    $messages = collect($sms->messages);

    expect($messages)->toHaveCount(2)
        ->and($messages->first()['message'])->toContain('verification code')
        ->and($messages->last()['message'])->toContain($order->order_number)
        ->and($messages->last()['message'])->toContain('/order-status/');
});

test('a failing sms sender does not stop an order from being placed', function () {
    $product = storefrontCheckoutProduct(['slug' => 'sms-link-failure-item']);
    storefrontCheckoutBranchWithStock($product);

    app()->instance(SendsSms::class, new class implements SendsSms
    {
        public function send(string $toE164, string $message): void
        {
            throw new RuntimeException('SMS gateway down');
        }
    });
    app()->forgetInstance(OrderPlacementNotifier::class);

    $this->post(route('cart.store'), ['product' => $product->slug, 'quantity' => 1]);
    $this->post(route('checkout.store'), checkoutPayload())->assertSessionDoesntHaveErrors();

    expect(Order::query()->count())->toBe(1);
});

test('a guest can order with the phone of a platform, branch or driver user without logging in', function (UserType $type) {
    $product = storefrontCheckoutProduct(['slug' => 'guest-role-phone-item-'.$type->value]);
    storefrontCheckoutBranchWithStock($product);
    $sms = app(FakeSmsSender::class);
    $sms->reset();

    $staff = User::factory()->create([
        'name' => 'Role Owner',
        'phone' => Phone::toE164('0410123456'),
        'user_type' => $type,
    ]);
    $usersBefore = User::query()->count();

    $this->post(route('cart.store'), ['product' => $product->slug, 'quantity' => 1]);
    $this->post(route('checkout.store'), checkoutPayload(['name' => 'Someone At The Door']))
        ->assertSessionDoesntHaveErrors();

    $order = Order::query()->firstOrFail();

    expect($order->customer_id)->toBe($staff->id)
        ->and($staff->fresh()->user_type)->toBe($type)
        ->and($staff->fresh()->name)->toBe('Role Owner')
        ->and(User::query()->count())->toBe($usersBefore)
        ->and($order->shipping_address_snapshot['name'])->toBe('Someone At The Door');

    $link = collect($sms->messages)
        ->where('to', Phone::toE164('0410123456'))
        ->first(fn (array $message): bool => str_contains($message['message'], '/order-status/'));

    expect($link)->not->toBeNull();

    preg_match('~https?://\S+~', $link['message'], $match);

    $this->get($match[0])
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/order-tracking')
            ->where('order.orderNumber', $order->order_number));

    $this->flushSession();
    $this->get(route('checkout.confirmation', $order))->assertNotFound();
})->with([
    'platform user' => [UserType::PLATFORM],
    'branch user' => [UserType::BRANCH],
    'driver user' => [UserType::DRIVER],
]);

test('a guest cannot order with the phone of an inactive staff user', function () {
    $product = storefrontCheckoutProduct(['slug' => 'guest-inactive-staff-item']);
    storefrontCheckoutBranchWithStock($product);

    User::factory()->create([
        'phone' => Phone::toE164('0410123456'),
        'user_type' => UserType::DRIVER,
        'is_active' => false,
    ]);

    $this->post(route('cart.store'), ['product' => $product->slug, 'quantity' => 1]);

    $this->post(route('checkout.store'), checkoutPayload())
        ->assertSessionHasErrors('mobile');

    expect(Order::query()->count())->toBe(0);
});

test('a guest cannot order with the phone of an inactive account', function () {
    $product = storefrontCheckoutProduct(['slug' => 'guest-inactive-phone-item']);
    storefrontCheckoutBranchWithStock($product);

    User::factory()->create([
        'phone' => Phone::toE164('0410123456'),
        'registration_status' => CustomerRegistrationStatus::Registered,
        'is_active' => false,
    ]);

    $this->post(route('cart.store'), ['product' => $product->slug, 'quantity' => 1]);

    $this->post(route('checkout.store'), checkoutPayload())
        ->assertSessionHasErrors('mobile');

    expect(Order::query()->count())->toBe(0);
});

test('a disabled payment method is rejected', function () {
    $user = User::factory()->create();
    $product = storefrontCheckoutProduct(['slug' => 'disabled-payment-item']);
    storefrontCheckoutBranchWithStock($product);

    PaymentGatewaySetting::query()
        ->where('key', PaymentGatewaySetting::KEY_POS)
        ->update(['enabled' => false]);
    app(PaymentGatewaySettingsService::class)->refresh();

    $this->actingAs($user, 'customer')
        ->post(route('cart.store'), [
            'product' => $product->slug,
            'quantity' => 1,
        ]);

    $this->actingAs($user, 'customer')
        ->from(route('checkout'))
        ->post(route('checkout.store'), checkoutPayload([
            'payment_method' => PaymentGatewaySetting::KEY_POS,
        ]))
        ->assertRedirect(route('checkout'))
        ->assertSessionHasErrors('payment_method');

    expect(Order::query()->count())->toBe(0)
        ->and(CartItem::query()->count())->toBe(1);
});

test('a cart with local shortfall accepts late delivery on one fulfillment without leaking branches to the customer', function () {
    $user = User::factory()->create();
    $product = storefrontCheckoutProduct(['slug' => 'split-branch-item']);

    $near = Branch::factory()->create([
        'latitude' => -33.8688,
        'longitude' => 151.2093,
        'delivery_radius_km' => 3000,
        'status' => true,
    ]);
    BranchArea::factory()->forBranch($near)->create([
        'polygon' => Polygon::squareAround(-33.8688, 151.2093, 0.05),
        'is_active' => true,
    ]);

    Inventory::factory()->create([
        'branch_id' => $near->id,
        'product_id' => $product->id,
        'variant_id' => null,
        'quantity' => 2,
        'status' => true,
    ]);

    $this->actingAs($user, 'customer')
        ->post(route('cart.store'), [
            'product' => $product->slug,
            'quantity' => 5,
        ]);

    $this->actingAs($user, 'customer')
        ->post(route('checkout.store'), checkoutPayload([
            'accept_late_delivery' => true,
            'delivery_method' => 'express',
            'delivery_date' => app(DeliveryShiftAvailability::class)->lateMinDate(),
        ]))
        ->assertRedirect();

    $order = Order::query()->firstOrFail();

    expect($order->fulfillments)->toHaveCount(1)
        ->and($order->fulfillment->branch_id)->toBe($near->id)
        ->and((int) $order->items->sum('quantity'))->toBe(5)
        ->and($order->items->first()->is_late_delivery)->toBeTrue();

    $this->actingAs($user, 'customer')
        ->get(route('checkout.confirmation', $order))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/checkout-confirmation')
            ->where('order.hasLateDeliveryItems', true)
            ->missing('order.id')
            ->missing('order.branch')
            ->missing('order.fulfillments')
            ->missing('order.branchName')
            ->missing('order.transfers'));
});

test('placing a multi-line area order with a late line issues a flat number of inventory queries', function () {
    $user = User::factory()->create();
    $local1 = storefrontCheckoutProduct(['slug' => 'inv-flat-local-1']);
    $local2 = storefrontCheckoutProduct(['slug' => 'inv-flat-local-2']);
    $late = storefrontCheckoutProduct(['slug' => 'inv-flat-late']);

    $destination = Branch::factory()->create([
        'latitude' => -33.8688,
        'longitude' => 151.2093,
        'delivery_radius_km' => 3000,
        'status' => true,
    ]);
    BranchArea::factory()->forBranch($destination)->create([
        'polygon' => Polygon::squareAround(-33.8688, 151.2093, 0.05),
        'is_active' => true,
    ]);

    Inventory::factory()->create(['branch_id' => $destination->id, 'product_id' => $local1->id, 'quantity' => 5, 'status' => true]);
    Inventory::factory()->create(['branch_id' => $destination->id, 'product_id' => $local2->id, 'quantity' => 5, 'status' => true]);

    foreach ([$local1, $local2, $late] as $product) {
        $this->actingAs($user, 'customer')
            ->post(route('cart.store'), [
                'product' => $product->slug,
                'quantity' => 1,
            ]);
    }

    $queries = [];
    DB::listen(function ($query) use (&$queries): void {
        if (str_contains($query->sql, 'inventories')) {
            $queries[] = $query->sql;
        }
    });

    $this->actingAs($user, 'customer')
        ->post(route('checkout.store'), checkoutPayload([
            'accept_late_delivery' => true,
            'delivery_method' => 'express',
            'delivery_date' => app(DeliveryShiftAvailability::class)->lateMinDate(),
        ]))
        ->assertRedirect();

    // sellableByBranch + inventory map + lockOrdered + 2 queries per reserved local row.
    expect($queries)->toHaveCount(7, implode("\n", $queries));

    $order = Order::query()->firstOrFail();
    expect($order->items)->toHaveCount(3)
        ->and($order->fulfillments)->toHaveCount(1)
        ->and($order->items->firstWhere('product_id', $late->id)?->is_late_delivery)->toBeTrue();
});

test('placing an order writes an area allocation decision log for the matched branch', function () {
    $user = User::factory()->create();
    $product = storefrontCheckoutProduct(['slug' => 'decision-log-item']);
    $winner = storefrontCheckoutBranchWithStock($product);

    $this->actingAs($user, 'customer')
        ->post(route('cart.store'), [
            'product' => $product->slug,
            'quantity' => 1,
        ]);

    $this->actingAs($user, 'customer')
        ->post(route('checkout.store'), checkoutPayload())
        ->assertRedirect();

    $order = Order::query()->firstOrFail();

    $log = AllocationDecisionLog::query()->where('order_id', $order->id)->firstOrFail();

    expect($log->winning_branch_id)->toBe($winner->id)
        ->and($log->considered_branches)->not->toBeEmpty()
        ->and($log->considered_branches[0]['branch_id'])->toBe($winner->id)
        // The default checkout payload is same-day, so the zone branch inside the radius wins.
        ->and($log->considered_branches[0]['allocation'] ?? null)->toBe('same_day_area_radius')
        ->and($log->rejected_branches)->toBe([]);
});

test('order line items snapshot product details so a later catalog change does not affect the order', function () {
    $user = User::factory()->create();
    $product = storefrontCheckoutProduct([
        'slug' => 'snapshot-item',
        'name' => 'Original Product Name',
        'selling_price' => 60,
    ]);
    storefrontCheckoutBranchWithStock($product);

    $this->actingAs($user, 'customer')
        ->post(route('cart.store'), [
            'product' => $product->slug,
            'quantity' => 1,
        ]);

    $this->actingAs($user, 'customer')
        ->post(route('checkout.store'), checkoutPayload())
        ->assertRedirect();

    $order = Order::query()->firstOrFail();
    $item = $order->items()->firstOrFail();

    expect($item->product_name_snapshot)->toBe('Original Product Name')
        ->and((float) $item->unit_price)->toBe(60.0);

    $product->update(['name' => 'Renamed Product', 'selling_price' => 999]);

    $item->refresh();
    $order->refresh();

    expect($item->product_name_snapshot)->toBe('Original Product Name')
        ->and((float) $item->unit_price)->toBe(60.0)
        ->and((float) $order->total_amount)->not->toBe(999.0);
});

test('a first order waits for confirmation and a returning customer is placed immediately', function () {
    $user = User::factory()->create();
    $product = storefrontCheckoutProduct(['slug' => 'first-order-pending']);
    storefrontCheckoutBranchWithStock($product, 20);

    $this->actingAs($user, 'customer')
        ->post(route('cart.store'), [
            'product' => $product->slug,
            'quantity' => 1,
        ]);

    $this->actingAs($user, 'customer')
        ->post(route('checkout.store'), checkoutPayload())
        ->assertRedirect();

    $first = Order::query()->where('customer_id', $user->id)->firstOrFail();
    $history = $first->statusHistories()->first();

    expect($first->status)->toBe(OrderStatus::Pending)
        ->and($history->changed_by)->toBe($user->id)
        ->and($history->new_status)->toBe('pending');

    $first->forceFill(['status' => OrderStatus::Delivered])->save();
    $first->fulfillment->forceFill(['status' => FulfillmentStatus::Delivered])->save();

    $this->actingAs($user, 'customer')
        ->post(route('cart.store'), [
            'product' => $product->slug,
            'quantity' => 1,
        ]);

    $this->actingAs($user, 'customer')
        ->post(route('checkout.store'), checkoutPayload())
        ->assertRedirect();

    $second = Order::query()->where('customer_id', $user->id)->whereKeyNot($first->id)->firstOrFail();

    expect($second->status)->toBe(OrderStatus::Placed);
});

test('checkout pre-fills the single saved address and sends none when there is no saved address', function () {
    $product = storefrontCheckoutProduct(['slug' => 'saved-address-prefill']);
    $withoutAddress = User::factory()->create();
    $withAddress = User::factory()->create();
    Address::factory()->for($withAddress)->create([
        'receiver_name' => 'Grace Hopper',
        'phone' => '0411222333',
        'address' => '9 Saved Lane',
        'city' => 'Sydney',
        'postal_code' => '2000',
        'latitude' => -33.8688,
        'longitude' => 151.2093,
    ]);

    foreach ([$withoutAddress, $withAddress] as $user) {
        $this->actingAs($user, 'customer')
            ->post(route('cart.store'), ['product' => $product->slug, 'quantity' => 1]);
    }

    $this->actingAs($withoutAddress, 'customer')
        ->get(route('checkout'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('savedAddress', null)
            ->missing('addresses')
            ->missing('defaultAddressId'));

    $this->actingAs($withAddress, 'customer')
        ->get(route('checkout'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('savedAddress.name', 'Grace Hopper')
            ->where('savedAddress.mobile', '0411222333')
            ->where('savedAddress.address', '9 Saved Lane')
            ->where('savedAddress.latitude', '-33.8688000')
            ->where('savedAddress.longitude', '151.2093000'));
});

test('saving as default keeps one address per customer and updates it in place', function () {
    $user = User::factory()->create();
    $product = storefrontCheckoutProduct(['slug' => 'save-default-address']);
    storefrontCheckoutBranchWithStock($product, 20);

    foreach (['1 First Street', '2 Second Street'] as $street) {
        $this->actingAs($user, 'customer')
            ->post(route('cart.store'), ['product' => $product->slug, 'quantity' => 1]);

        $this->actingAs($user, 'customer')
            ->post(route('checkout.store'), checkoutPayload([
                'address' => $street,
                'save_as_default' => true,
            ]))
            ->assertRedirect();
    }

    $address = Address::query()->where('user_id', $user->id)->sole();

    expect($address->address)->toBe('2 Second Street')
        ->and(Order::query()->where('customer_id', $user->id)->pluck('address_id')->unique()->all())
        ->toBe([$address->id]);
});

test('placing an order without saving as default leaves the saved address untouched', function () {
    $user = User::factory()->create();
    $saved = Address::factory()->for($user)->create(['address' => '9 Saved Lane']);
    $product = storefrontCheckoutProduct(['slug' => 'no-save-default']);
    storefrontCheckoutBranchWithStock($product);

    $this->actingAs($user, 'customer')
        ->post(route('cart.store'), ['product' => $product->slug, 'quantity' => 1]);

    $this->actingAs($user, 'customer')
        ->post(route('checkout.store'), checkoutPayload(['address' => '5 One Off Road']))
        ->assertRedirect();

    $order = Order::query()->where('customer_id', $user->id)->firstOrFail();

    expect(Address::query()->where('user_id', $user->id)->sole()->is($saved))->toBeTrue()
        ->and($saved->refresh()->address)->toBe('9 Saved Lane')
        ->and($order->address_id)->toBeNull()
        ->and($order->shipping_address_snapshot['address'])->toBe('5 One Off Road');
});

/**
 * Zone branch sits outside the same-day radius; the other-zone branch is a few km from the pin.
 *
 * @return array{zone: Branch, nearby: Branch}
 */
function sameDayRadiusBranches(Product $product): array
{
    $zone = Branch::factory()->create(['latitude' => -34.5, 'longitude' => 151.2, 'status' => true]);
    $nearby = Branch::factory()->create(['latitude' => -33.87, 'longitude' => 151.21, 'status' => true]);

    BranchArea::factory()->forBranch($zone)->create([
        'polygon' => Polygon::squareAround(-33.8688, 151.2093, 0.05),
        'is_active' => true,
    ]);
    BranchArea::factory()->forBranch($nearby)->create([
        'polygon' => Polygon::squareAround(-30.0, 150.0, 0.05),
        'is_active' => true,
    ]);

    foreach ([$zone, $nearby] as $branch) {
        Inventory::factory()->create([
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'variant_id' => null,
            'quantity' => 10,
            'status' => true,
        ]);
    }

    return ['zone' => $zone, 'nearby' => $nearby];
}

test('same-day checkout uses a nearby branch from another zone when the zone branch is outside the radius', function () {
    $user = User::factory()->create();
    $product = storefrontCheckoutProduct(['slug' => 'same-day-radius-item']);
    $branches = sameDayRadiusBranches($product);

    $this->actingAs($user, 'customer')->post(route('cart.store'), ['product' => $product->slug, 'quantity' => 1]);

    $this->actingAs($user, 'customer')
        ->postJson(route('checkout.shipping'), [
            'latitude' => -33.8688,
            'longitude' => 151.2093,
            'delivery_method' => 'regular',
        ])
        ->assertOk()
        ->assertJsonPath('matchedBranchId', $branches['nearby']->id);

    $this->actingAs($user, 'customer')
        ->post(route('checkout.store'), checkoutPayload(['delivery_method' => 'regular']))
        ->assertRedirect();

    expect(Order::query()->firstOrFail()->fulfillment->branch_id)->toBe($branches['nearby']->id);
});

test('late delivery checkout keeps the zone branch even when another branch is nearer', function () {
    $user = User::factory()->create();
    $product = storefrontCheckoutProduct(['slug' => 'late-zone-item']);
    $branches = sameDayRadiusBranches($product);

    $this->actingAs($user, 'customer')->post(route('cart.store'), ['product' => $product->slug, 'quantity' => 1]);

    $this->actingAs($user, 'customer')
        ->postJson(route('checkout.shipping'), [
            'latitude' => -33.8688,
            'longitude' => 151.2093,
            'delivery_method' => 'express',
        ])
        ->assertOk()
        ->assertJsonPath('matchedBranchId', $branches['zone']->id);

    $this->actingAs($user, 'customer')
        ->post(route('checkout.store'), checkoutPayload([
            'delivery_method' => 'express',
            'delivery_date' => app(DeliveryShiftAvailability::class)->lateMinDate(),
        ]))
        ->assertRedirect();

    expect(Order::query()->firstOrFail()->fulfillment->branch_id)->toBe($branches['zone']->id);
});

test('a repeat customer order is placed straight away and reserves a branch driver without changing status', function () {
    $user = User::factory()->create();
    Order::factory()->delivered()->create(['customer_id' => $user->id]);
    $product = storefrontCheckoutProduct(['slug' => 'repeat-customer-driver-item']);
    $branch = storefrontCheckoutBranchWithStock($product);

    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'is_active' => true,
        'branch_id' => null,
    ]);
    DriverProfile::factory()->available()->create(['user_id' => $driver->id]);
    DriverBranchAssignment::factory()->create([
        'driver_id' => $driver->id,
        'branch_id' => $branch->id,
        'status' => DriverAssignmentStatus::Active,
    ]);
    scheduleDriverOnDuty($driver, $branch);

    $this->actingAs($user, 'customer')->post(route('cart.store'), ['product' => $product->slug, 'quantity' => 1]);
    $this->actingAs($user, 'customer')
        ->post(route('checkout.store'), checkoutPayload())
        ->assertRedirect();

    $fulfillment = Order::query()->latest('id')->firstOrFail()->fulfillment;

    expect($fulfillment->status)->toBe(FulfillmentStatus::Placed)
        ->and($fulfillment->reserved_driver_id)->toBe($driver->id)
        ->and($fulfillment->driver_id)->toBeNull();
});

test('a new customer order stays pending and has no driver reserved until staff confirm it', function () {
    $user = User::factory()->create();
    $product = storefrontCheckoutProduct(['slug' => 'new-customer-driver-item']);
    $branch = storefrontCheckoutBranchWithStock($product);

    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'is_active' => true,
        'branch_id' => null,
    ]);
    DriverProfile::factory()->available()->create(['user_id' => $driver->id]);
    DriverBranchAssignment::factory()->create([
        'driver_id' => $driver->id,
        'branch_id' => $branch->id,
        'status' => DriverAssignmentStatus::Active,
    ]);
    scheduleDriverOnDuty($driver, $branch);

    $this->actingAs($user, 'customer')->post(route('cart.store'), ['product' => $product->slug, 'quantity' => 1]);
    $this->actingAs($user, 'customer')
        ->post(route('checkout.store'), checkoutPayload())
        ->assertRedirect();

    $fulfillment = Order::query()->latest('id')->firstOrFail()->fulfillment;

    expect($fulfillment->status)->toBe(FulfillmentStatus::Pending)
        ->and($fulfillment->reserved_driver_id)->toBeNull();
});
