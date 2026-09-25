<?php

use App\Enums\CartStatus;
use App\Enums\CouponType;
use App\Enums\PaymentStatus;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\CouponRedemption;
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

function checkoutPayload(array $overrides = []): array
{
    return array_merge([
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
        'mobile' => '0410123456',
        'address' => '1 Test Street',
        'city' => 'Sydney',
        'postal_code' => '2000',
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
            ->has('deliveryMethods'));
});

test('place order writes order payment and redemption then clears ordered lines', function () {
    $user = User::factory()->create();
    $product = storefrontCheckoutProduct(['slug' => 'place-order-item']);
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
        ->and($order->branch_id)->toBeNull();

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

test('a disabled payment method is rejected', function () {
    $user = User::factory()->create();
    $product = storefrontCheckoutProduct(['slug' => 'disabled-payment-item']);

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
