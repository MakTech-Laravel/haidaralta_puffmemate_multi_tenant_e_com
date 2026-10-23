<?php

use App\Enums\CartStatus;
use App\Enums\CouponType;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Pack;
use App\Models\PackItem;
use App\Models\PaymentGatewaySetting;
use App\Models\Product;
use App\Models\User;
use App\Services\Application\PaymentGatewaySettingsService;
use App\Services\Catalog\DeliverySettingsService;
use App\Services\Order\DriverDeliveryService;
use App\Services\Order\FulfillmentStatusService;
use App\Services\Order\OrderPlacementNotifier;
use App\Services\Sms\FakeSmsSender;
use App\Support\Storefront\PackCheckoutSession;
use Database\Seeders\ApplicationSettingsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(ApplicationSettingsSeeder::class);
    app(PaymentGatewaySettingsService::class)->ensureDefaults();
    app(DeliverySettingsService::class)->ensureDefaults();
});

function packBuyBranch(): Branch
{
    return Branch::factory()->create([
        'name' => 'Richmond Hub',
        'slug' => 'richmond',
        'latitude' => -33.8688,
        'longitude' => 151.2093,
        'delivery_radius_km' => 50,
        'status' => true,
    ]);
}

function packBuyProduct(string $name = 'Gold 20s', float $price = 40): Product
{
    return Product::factory()->create([
        'name' => $name,
        'selling_price' => $price,
        'regular_price' => $price + 10,
        'status' => true,
    ]);
}

/**
 * @return array{branch: Branch, pack: Pack, products: list<Product>}
 */
function packBuyOffer(int $quantity = 5, float $packPrice = 80): array
{
    $branch = packBuyBranch();
    $first = packBuyProduct('Gold 20s', 50);
    $second = packBuyProduct('Clipper', 30);

    foreach ([$first, $second] as $product) {
        Inventory::factory()->create([
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'variant_id' => null,
            'quantity' => 100,
            'status' => true,
        ]);
    }

    $pack = Pack::factory()->for($branch)->create([
        'name' => 'Carton Saver',
        'slug' => 'carton-saver',
        'subtotal' => '100.00',
        'pack_price' => number_format($packPrice, 2, '.', ''),
        'quantity' => $quantity,
        'status' => true,
        'starts_at' => null,
        'ends_at' => now()->addWeek(),
    ]);

    PackItem::factory()->for($pack)->for($first)->create(['quantity' => 1]);
    PackItem::factory()->for($pack)->for($second)->create(['quantity' => 1]);

    return [
        'branch' => $branch,
        'pack' => $pack->fresh(['items.product', 'branch']),
        'products' => [$first, $second],
    ];
}

function packCheckoutPayload(array $overrides = []): array
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
        'payment_method' => PaymentGatewaySetting::KEY_COD,
        'terms' => true,
    ], $overrides);
}

test('guests are redirected to login when buying a pack', function () {
    $offer = packBuyOffer();

    $this->post(route('packs-saves.buy', [
        'branch' => $offer['branch']->slug,
        'pack' => $offer['pack']->slug,
    ]), ['quantity' => 1])
        ->assertRedirect(route('login'));
});

test('buy now stores pack checkout session and opens checkout without coupons', function () {
    $user = User::factory()->create();
    $offer = packBuyOffer();

    $this->actingAs($user, 'customer')
        ->post(route('packs-saves.buy', [
            'branch' => $offer['branch']->slug,
            'pack' => $offer['pack']->slug,
        ]), ['quantity' => 2])
        ->assertRedirect(route('checkout'))
        ->assertSessionHas(PackCheckoutSession::SESSION_KEY, [
            'pack_id' => $offer['pack']->id,
            'quantity' => 2,
        ])
        ->assertSessionMissing('storefront.checkout_coupon');

    $this->actingAs($user, 'customer')
        ->get(route('checkout'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/checkout')
            ->where('checkoutMode', 'pack')
            ->where('couponsAllowed', false)
            ->where('itemCount', 2)
            ->where('subtotal', 160)
            ->where('items.0.name', 'Carton Saver')
            ->where('appliedCoupon', null));
});

test('coupons cannot be applied during pack checkout', function () {
    $user = User::factory()->create();
    $offer = packBuyOffer();

    Coupon::factory()->create([
        'code' => 'SAVE10',
        'type' => CouponType::Fixed,
        'value' => 10,
        'min_subtotal' => 10,
        'status' => true,
    ]);

    $this->actingAs($user, 'customer')
        ->post(route('packs-saves.buy', [
            'branch' => $offer['branch']->slug,
            'pack' => $offer['pack']->slug,
        ]), ['quantity' => 1])
        ->assertRedirect(route('checkout'));

    $this->actingAs($user, 'customer')
        ->from(route('checkout'))
        ->post(route('checkout.coupon'), ['coupon' => 'SAVE10'])
        ->assertRedirect(route('checkout'))
        ->assertSessionHasErrors('coupon');
});

test('placing a pack order creates order lines, decrements pack qty, and leaves the product cart alone', function () {
    $user = User::factory()->create();
    $offer = packBuyOffer(quantity: 4, packPrice: 80);
    $extraProduct = packBuyProduct('Cart Product', 25);

    Inventory::factory()->create([
        'branch_id' => $offer['branch']->id,
        'product_id' => $extraProduct->id,
        'variant_id' => null,
        'quantity' => 10,
        'status' => true,
    ]);

    $this->actingAs($user, 'customer')
        ->post(route('cart.store'), [
            'product' => $extraProduct->slug,
            'quantity' => 2,
        ]);

    expect(CartItem::query()->sum('quantity'))->toBe(2);

    $this->actingAs($user, 'customer')
        ->post(route('packs-saves.buy', [
            'branch' => $offer['branch']->slug,
            'pack' => $offer['pack']->slug,
        ]), ['quantity' => 2])
        ->assertRedirect(route('checkout'));

    $this->actingAs($user, 'customer')
        ->post(route('checkout.store'), packCheckoutPayload())
        ->assertRedirect();

    $order = Order::query()->firstOrFail();

    expect($order->pack_id)->toBe($offer['pack']->id)
        ->and($order->pack_quantity)->toBe(2)
        ->and((float) $order->subtotal)->toBe(160.0)
        ->and((float) $order->discount)->toBe(0.0)
        ->and($order->coupon_id)->toBeNull()
        ->and($order->coupon_code)->toBeNull()
        ->and($order->items)->toHaveCount(2)
        ->and((float) $order->items->sum('subtotal'))->toBe(160.0)
        ->and($offer['pack']->fresh()->quantity)->toBe(2)
        ->and(CartItem::query()->sum('quantity'))->toBe(2)
        ->and(Cart::query()->where('status', CartStatus::Active)->exists())->toBeTrue()
        ->and(session()->has(PackCheckoutSession::SESSION_KEY))->toBeFalse();
});

test('buy now rejects sold-out packs', function () {
    $user = User::factory()->create();
    $offer = packBuyOffer(quantity: 1);

    $this->actingAs($user, 'customer')
        ->from(route('packs-saves.show', [
            'branch' => $offer['branch']->slug,
            'pack' => $offer['pack']->slug,
        ]))
        ->post(route('packs-saves.buy', [
            'branch' => $offer['branch']->slug,
            'pack' => $offer['pack']->slug,
        ]), ['quantity' => 2])
        ->assertRedirect()
        ->assertSessionHasErrors('quantity');
});

test('inactive packs cannot be bought', function () {
    $user = User::factory()->create();
    $offer = packBuyOffer();
    $offer['pack']->forceFill(['status' => false])->save();

    $this->actingAs($user, 'customer')
        ->post(route('packs-saves.buy', [
            'branch' => $offer['branch']->slug,
            'pack' => $offer['pack']->slug,
        ]), ['quantity' => 1])
        ->assertNotFound();
});

test('delivering a pack order does not require reserved inventory', function () {
    $customer = User::factory()->create([
        'phone' => '+61410123456',
    ]);
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'email_verified_at' => now(),
    ]);
    $offer = packBuyOffer(quantity: 3);

    $this->actingAs($customer, 'customer')
        ->post(route('packs-saves.buy', [
            'branch' => $offer['branch']->slug,
            'pack' => $offer['pack']->slug,
        ]), ['quantity' => 1]);

    $this->actingAs($customer, 'customer')
        ->post(route('checkout.store'), packCheckoutPayload())
        ->assertRedirect();

    $order = Order::query()->firstOrFail();
    $fulfillment = $order->fulfillment()->firstOrFail();

    $fulfillment->forceFill([
        'driver_id' => $driver->id,
        'status' => FulfillmentStatus::Arrived,
    ])->save();
    $order->forceFill(['status' => OrderStatus::Arrived])->save();

    app(OrderPlacementNotifier::class)->issue($order->refresh());
    $code = app(FakeSmsSender::class)->lastCode();

    app(DriverDeliveryService::class)->complete($fulfillment->refresh(), $driver, (string) $code);

    expect($fulfillment->refresh()->status)->toBe(FulfillmentStatus::Delivered)
        ->and($order->refresh()->status)->toBe(OrderStatus::Delivered)
        ->and($order->payment->status)->toBe(PaymentStatus::Collected);
});

test('cancelling a pack order restores pack quantity', function () {
    $user = User::factory()->create();
    $offer = packBuyOffer(quantity: 3);

    $this->actingAs($user, 'customer')
        ->post(route('packs-saves.buy', [
            'branch' => $offer['branch']->slug,
            'pack' => $offer['pack']->slug,
        ]), ['quantity' => 2]);

    $this->actingAs($user, 'customer')
        ->post(route('checkout.store'), packCheckoutPayload())
        ->assertRedirect();

    $order = Order::query()->firstOrFail();
    $fulfillment = $order->fulfillment()->firstOrFail();

    expect($offer['pack']->fresh()->quantity)->toBe(1);

    app(FulfillmentStatusService::class)
        ->cancel($fulfillment, $user, 'Customer cancelled');

    expect($offer['pack']->fresh()->quantity)->toBe(3)
        ->and($offer['pack']->fresh()->status)->toBeTrue()
        ->and($order->fresh()->pack_quantity)->toBe(0);
});
