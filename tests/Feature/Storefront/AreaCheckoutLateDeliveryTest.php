<?php

use App\Models\AllocationDecisionLog;
use App\Models\Branch;
use App\Models\BranchArea;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\PaymentGatewaySetting;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Models\User;
use App\Services\Application\PaymentGatewaySettingsService;
use App\Services\Catalog\DeliverySettingsService;
use App\Support\Delivery\DeliveryShiftAvailability;
use App\Support\Geo\Polygon;
use Database\Seeders\ApplicationSettingsSeeder;

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

function areaCheckoutProduct(string $slug): Product
{
    return Product::factory()->create([
        'slug' => $slug,
        'selling_price' => 40,
        'status' => true,
    ]);
}

/**
 * @return array{0: Branch, 1: BranchArea}
 */
function areaCheckoutBranchWithZone(float $lat = -33.8688, float $lng = 151.2093, float $delta = 0.05): array
{
    $branch = Branch::factory()->create([
        'latitude' => $lat,
        'longitude' => $lng,
        'status' => true,
        'delivery_radius_km' => 1,
    ]);
    $area = BranchArea::factory()->forBranch($branch)->create([
        'latitude' => $lat,
        'longitude' => $lng,
        'polygon' => Polygon::squareAround($lat, $lng, $delta),
        'is_active' => true,
    ]);

    return [$branch, $area];
}

function areaCheckoutPayload(array $overrides = []): array
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

test('cart checkout routes to the nearest branch whose area contains the pin', function () {
    $user = User::factory()->create();
    $product = areaCheckoutProduct('area-near-stock');

    [$near] = areaCheckoutBranchWithZone(-33.8688, 151.2093);
    [$far] = areaCheckoutBranchWithZone(-33.9, 151.25);

    Inventory::factory()->create([
        'branch_id' => $near->id,
        'product_id' => $product->id,
        'quantity' => 5,
        'status' => true,
    ]);
    Inventory::factory()->create([
        'branch_id' => $far->id,
        'product_id' => $product->id,
        'quantity' => 50,
        'status' => true,
    ]);

    // Both areas cover the pin; near should win by distance.
    BranchArea::query()->where('id', '!=', 0)->update([
        'polygon' => Polygon::squareAround(-33.8688, 151.2093, 0.1),
    ]);

    $this->actingAs($user, 'customer')
        ->post(route('cart.store'), ['product' => $product->slug, 'quantity' => 1]);

    $this->actingAs($user, 'customer')
        ->post(route('checkout.store'), areaCheckoutPayload())
        ->assertRedirect();

    $order = Order::query()->firstOrFail();

    expect($order->fulfillment->branch_id)->toBe($near->id)
        ->and($order->items->first()->is_late_delivery)->toBeFalse()
        ->and(StockTransfer::query()->count())->toBe(0);
});

test('cart checkout outside every area but inside Australia uses the nearest active branch', function () {
    $user = User::factory()->create();
    $product = areaCheckoutProduct('area-outside-au-ok');
    [$branch] = areaCheckoutBranchWithZone(-33.8688, 151.2093, 0.01);

    Inventory::factory()->create([
        'branch_id' => $branch->id,
        'product_id' => $product->id,
        'quantity' => 5,
        'status' => true,
    ]);

    $this->actingAs($user, 'customer')
        ->post(route('cart.store'), ['product' => $product->slug, 'quantity' => 1]);

    // Adelaide — inside AU bounds, outside the tiny Sydney area polygon. Late delivery
    // has no radius limit, so the nearest active branch still fulfils it.
    $this->actingAs($user, 'customer')
        ->post(route('checkout.store'), areaCheckoutPayload([
            'latitude' => -34.9285,
            'longitude' => 138.6007,
            'city' => 'Adelaide',
            'postal_code' => '5000',
            'delivery_method' => 'express',
            'delivery_date' => '2026-11-10',
        ]))
        ->assertRedirect();

    $order = Order::query()->firstOrFail();

    expect($order->fulfillment->branch_id)->toBe($branch->id)
        ->and(AllocationDecisionLog::query()->where('order_id', $order->id)->firstOrFail()
            ->considered_branches[0]['allocation'] ?? null)->toBe('nearest_active');
});

test('same day delivery is rejected when the address is outside the same-day radius', function () {
    $user = User::factory()->create();
    $product = areaCheckoutProduct('area-same-day-outside-radius');
    [$branch] = areaCheckoutBranchWithZone(-33.8688, 151.2093, 0.01);

    Inventory::factory()->create([
        'branch_id' => $branch->id,
        'product_id' => $product->id,
        'quantity' => 5,
        'status' => true,
    ]);

    $this->actingAs($user, 'customer')
        ->post(route('cart.store'), ['product' => $product->slug, 'quantity' => 1]);

    $this->actingAs($user, 'customer')
        ->from(route('checkout'))
        ->post(route('checkout.store'), areaCheckoutPayload([
            'latitude' => -34.9285,
            'longitude' => 138.6007,
            'city' => 'Adelaide',
            'postal_code' => '5000',
            'delivery_method' => 'regular',
        ]))
        ->assertRedirect(route('checkout'))
        ->assertSessionHasErrors(['delivery_method' => DeliveryShiftAvailability::sameDayOutOfRadiusMessage()]);

    expect(Order::query()->count())->toBe(0);
});

test('shipping quote reports whether the pin is inside the same-day radius', function () {
    $user = User::factory()->create();
    $product = areaCheckoutProduct('area-quote-same-day-radius');
    [$branch] = areaCheckoutBranchWithZone(-33.8688, 151.2093, 0.01);

    Inventory::factory()->create([
        'branch_id' => $branch->id,
        'product_id' => $product->id,
        'quantity' => 5,
        'status' => true,
    ]);

    $this->actingAs($user, 'customer')
        ->post(route('cart.store'), ['product' => $product->slug, 'quantity' => 1]);

    $this->actingAs($user, 'customer')
        ->postJson(route('checkout.shipping'), [
            'latitude' => -33.8688,
            'longitude' => 151.2093,
            'delivery_method' => 'express',
        ])
        ->assertOk()
        ->assertJsonPath('sameDayInRadius', true)
        ->assertJsonPath('sameDayRadiusKm', 15);

    $this->actingAs($user, 'customer')
        ->postJson(route('checkout.shipping'), [
            'latitude' => -34.9285,
            'longitude' => 138.6007,
            'delivery_method' => 'express',
        ])
        ->assertOk()
        ->assertJsonPath('sameDayInRadius', false);
});

test('cart checkout rejects a pin outside Australia', function () {
    $user = User::factory()->create();
    $product = areaCheckoutProduct('area-outside-au-block');
    [$branch] = areaCheckoutBranchWithZone();

    Inventory::factory()->create([
        'branch_id' => $branch->id,
        'product_id' => $product->id,
        'quantity' => 5,
        'status' => true,
    ]);

    $this->actingAs($user, 'customer')
        ->post(route('cart.store'), ['product' => $product->slug, 'quantity' => 1]);

    $this->actingAs($user, 'customer')
        ->from(route('checkout'))
        ->post(route('checkout.store'), areaCheckoutPayload([
            'latitude' => 27.4689623,
            'longitude' => 153.0235009,
        ]))
        ->assertRedirect(route('checkout'))
        ->assertSessionHasErrors('address');

    expect(Order::query()->count())->toBe(0);
});

test('shortfall without accept_late_delivery fails and does not create an order', function () {
    $user = User::factory()->create();
    $product = areaCheckoutProduct('area-shortfall-block');
    [$branch] = areaCheckoutBranchWithZone();

    Inventory::factory()->create([
        'branch_id' => $branch->id,
        'product_id' => $product->id,
        'quantity' => 1,
        'status' => true,
    ]);

    $this->actingAs($user, 'customer')
        ->post(route('cart.store'), ['product' => $product->slug, 'quantity' => 3]);

    $this->actingAs($user, 'customer')
        ->from(route('checkout'))
        ->post(route('checkout.store'), areaCheckoutPayload())
        ->assertRedirect(route('checkout'))
        ->assertSessionHasErrors('accept_late_delivery');

    expect(Order::query()->count())->toBe(0);
});

test('accepting late delivery places the order, flags late lines, and reserves only local qty', function () {
    $user = User::factory()->create();
    $product = areaCheckoutProduct('area-shortfall-accept');
    [$branch] = areaCheckoutBranchWithZone();

    $inventory = Inventory::factory()->create([
        'branch_id' => $branch->id,
        'product_id' => $product->id,
        'quantity' => 2,
        'reserved_quantity' => 0,
        'status' => true,
    ]);

    $this->actingAs($user, 'customer')
        ->post(route('cart.store'), ['product' => $product->slug, 'quantity' => 5]);

    $this->actingAs($user, 'customer')
        ->post(route('checkout.store'), areaCheckoutPayload([
            'accept_late_delivery' => true,
            'delivery_method' => 'express',
            'delivery_date' => app(DeliveryShiftAvailability::class)->lateMinDate(),
        ]))
        ->assertRedirect();

    $order = Order::query()->firstOrFail();
    $item = $order->items->firstOrFail();

    expect($order->accepts_late_delivery)->toBeTrue()
        ->and($item->quantity)->toBe(5)
        ->and($item->is_late_delivery)->toBeTrue()
        ->and(StockTransfer::query()->count())->toBe(0)
        ->and($inventory->fresh()->reserved_quantity)->toBe(2);
});

test('same day delivery is rejected on delivery_method when items are on late delivery', function () {
    $user = User::factory()->create();
    $product = areaCheckoutProduct('area-shortfall-same-day');
    [$branch] = areaCheckoutBranchWithZone();

    Inventory::factory()->create([
        'branch_id' => $branch->id,
        'product_id' => $product->id,
        'quantity' => 1,
        'status' => true,
    ]);

    $this->actingAs($user, 'customer')
        ->post(route('cart.store'), ['product' => $product->slug, 'quantity' => 3]);

    $this->actingAs($user, 'customer')
        ->from(route('checkout'))
        ->post(route('checkout.store'), areaCheckoutPayload([
            'accept_late_delivery' => true,
            'delivery_method' => 'regular',
        ]))
        ->assertRedirect(route('checkout'))
        ->assertSessionHasErrors(['delivery_method' => DeliveryShiftAvailability::SAME_DAY_LATE_ITEMS_MESSAGE]);

    expect(Order::query()->count())->toBe(0);
});

test('same day delivery still works when every item is in stock', function () {
    $user = User::factory()->create();
    $product = areaCheckoutProduct('area-in-stock-same-day');
    [$branch] = areaCheckoutBranchWithZone();

    Inventory::factory()->create([
        'branch_id' => $branch->id,
        'product_id' => $product->id,
        'quantity' => 5,
        'status' => true,
    ]);

    $this->actingAs($user, 'customer')
        ->post(route('cart.store'), ['product' => $product->slug, 'quantity' => 2]);

    $this->actingAs($user, 'customer')
        ->post(route('checkout.store'), areaCheckoutPayload(['delivery_method' => 'regular']))
        ->assertRedirect();

    expect(Order::query()->count())->toBe(1);
});

test('shipping quote reports shortfall lines for the matched area branch', function () {
    $user = User::factory()->create();
    $product = areaCheckoutProduct('area-quote-shortfall');
    [$branch] = areaCheckoutBranchWithZone();

    Inventory::factory()->create([
        'branch_id' => $branch->id,
        'product_id' => $product->id,
        'quantity' => 1,
        'status' => true,
    ]);

    $this->actingAs($user, 'customer')
        ->post(route('cart.store'), ['product' => $product->slug, 'quantity' => 4]);

    $this->actingAs($user, 'customer')
        ->postJson(route('checkout.shipping'), [
            'latitude' => -33.8688,
            'longitude' => 151.2093,
            'delivery_method' => 'regular',
        ])
        ->assertOk()
        ->assertJsonPath('requiresLateDelivery', true)
        ->assertJsonPath('matchedBranchId', $branch->id)
        ->assertJsonPath('shortfallLines.0.shortfall', 3)
        ->assertJsonPath('shortfallLines.0.productName', $product->name);
});
