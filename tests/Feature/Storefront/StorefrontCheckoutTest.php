<?php

use App\Enums\CartStatus;
use App\Enums\CouponType;
use App\Enums\PaymentStatus;
use App\Models\Branch;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentGatewaySetting;
use App\Models\Product;
use App\Models\User;
use App\Services\Application\PaymentGatewaySettingsService;
use App\Services\Catalog\DeliverySettingsService;
use Database\Seeders\ApplicationSettingsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(ApplicationSettingsSeeder::class);
    app(PaymentGatewaySettingsService::class)->ensureDefaults();
    app(DeliverySettingsService::class)->ensureDefaults();
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
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
        'mobile' => '0410123456',
        'address' => '1 Test Street',
        'city' => 'Sydney',
        'postal_code' => '2000',
        'latitude' => -33.8688,
        'longitude' => 151.2093,
        'delivery_method' => 'australia-wide',
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
            ->has('paymentMethods')
            ->has('deliveryMethods', 2)
            ->where('deliveryMethods.0.formattedFee', 'Calculated at checkout')
            ->where('deliveryMethods.1.formattedFee', 'Calculated at checkout'));
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

    $this->actingAs($user, 'customer')
        ->post(route('checkout.store'), checkoutPayload([
            'coupon' => $coupon->code,
        ]))
        ->assertRedirect();

    $order = Order::query()->firstOrFail();

    expect($order->customer_id)->toBe($user->id)
        ->and($order->items)->toHaveCount(1)
        ->and((float) $order->discount)->toBe(10.0)
        ->and($order->payment_method)->toBe(PaymentGatewaySetting::KEY_COD)
        ->and($order->fulfillments)->toHaveCount(1);

    $payment = Payment::query()->where('order_id', $order->id)->firstOrFail();

    expect($payment->status)->toBe(PaymentStatus::Unpaid)
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
            ->where('order.orderNumber', $order->order_number));
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
            'first_name' => '',
        ]))
        ->assertRedirect(route('checkout'))
        ->assertSessionHasErrors('first_name');

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

test('guests cannot remove a checkout coupon', function () {
    $this->delete(route('checkout.coupon.destroy'))
        ->assertRedirect(route('login'));
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

test('a cart that spans branches splits into multiple fulfillments without leaking them to the customer', function () {
    $user = User::factory()->create();
    $product = storefrontCheckoutProduct(['slug' => 'split-branch-item']);

    $near = Branch::factory()->create([
        'latitude' => -33.8688,
        'longitude' => 151.2093,
        'delivery_radius_km' => 3000,
        'status' => true,
    ]);
    $far = Branch::factory()->create([
        'latitude' => -37.8136,
        'longitude' => 144.9631,
        'delivery_radius_km' => 3000,
        'status' => true,
    ]);

    Inventory::factory()->create([
        'branch_id' => $near->id,
        'product_id' => $product->id,
        'variant_id' => null,
        'quantity' => 2,
        'status' => true,
    ]);
    Inventory::factory()->create([
        'branch_id' => $far->id,
        'product_id' => $product->id,
        'variant_id' => null,
        'quantity' => 10,
        'status' => true,
    ]);

    $this->actingAs($user, 'customer')
        ->post(route('cart.store'), [
            'product' => $product->slug,
            'quantity' => 5,
        ]);

    $this->actingAs($user, 'customer')
        ->post(route('checkout.store'), checkoutPayload())
        ->assertRedirect();

    $order = Order::query()->firstOrFail();

    expect($order->fulfillments)->toHaveCount(2);

    $quantities = $order->fulfillments->map(fn ($fulfillment) => $fulfillment->items->sum('quantity'))->all();
    sort($quantities);

    expect($quantities)->toBe([2, 3]);

    $this->actingAs($user, 'customer')
        ->get(route('checkout.confirmation', $order))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/checkout-confirmation')
            ->missing('order.branch')
            ->missing('order.fulfillments')
            ->missing('order.branchName'));
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
