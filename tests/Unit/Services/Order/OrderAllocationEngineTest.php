<?php

use App\Enums\CartStatus;
use App\Models\Branch;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\User;
use App\Services\Order\OrderAllocationEngine;
use Illuminate\Validation\ValidationException;

function allocationCartItem(Product $product, int $quantity): CartItem
{
    $cart = Cart::query()->create([
        'user_id' => User::factory()->create()->id,
        'status' => CartStatus::Active,
    ]);

    return CartItem::query()->create([
        'cart_id' => $cart->id,
        'product_id' => $product->id,
        'variant_id' => null,
        'quantity' => $quantity,
        'price_snapshot' => 10,
    ]);
}

test('a single branch with enough stock fulfils the whole cart', function () {
    $product = Product::factory()->create();
    $branch = Branch::factory()->create([
        'latitude' => -33.8688,
        'longitude' => 151.2093,
        'delivery_radius_km' => 50,
        'status' => true,
    ]);
    Inventory::factory()->create([
        'branch_id' => $branch->id,
        'product_id' => $product->id,
        'quantity' => 10,
        'status' => true,
    ]);

    $plan = app(OrderAllocationEngine::class)->allocate(-33.8688, 151.2093, collect([allocationCartItem($product, 4)]));

    expect($plan['branch']->id)->toBe($branch->id)
        ->and($plan['transfers'])->toHaveCount(0)
        ->and($plan['lines'][0]['quantity'])->toBe(4)
        ->and($plan['lines'][0]['local_quantity'])->toBe(4);
});

test('a nearby branch with partial stock transfers the shortfall instead of splitting the order', function () {
    $product = Product::factory()->create();
    $near = Branch::factory()->create([
        'latitude' => -33.8688,
        'longitude' => 151.2093,
        'delivery_radius_km' => 3000,
        'status' => true,
        'fulfillment_priority' => 1,
    ]);
    $far = Branch::factory()->create([
        'latitude' => -37.8136,
        'longitude' => 144.9631,
        'delivery_radius_km' => 3000,
        'status' => true,
        'fulfillment_priority' => 100,
    ]);
    Inventory::factory()->create(['branch_id' => $near->id, 'product_id' => $product->id, 'quantity' => 3, 'status' => true]);
    Inventory::factory()->create(['branch_id' => $far->id, 'product_id' => $product->id, 'quantity' => 10, 'status' => true]);

    $plan = app(OrderAllocationEngine::class)->allocate(-33.8688, 151.2093, collect([allocationCartItem($product, 5)]));

    expect($plan['branch']->id)->toBe($far->id)
        ->and($plan['transfers'])->toHaveCount(0)
        ->and($plan['lines'][0]['local_quantity'])->toBe(5);
});

test('a pin nearer an empty unlimited-radius branch still uses farther local stock', function () {
    $product = Product::factory()->create();
    $nearEmpty = Branch::factory()->create([
        'latitude' => -32.9273,
        'longitude' => 151.7812,
        'delivery_radius_km' => null,
        'status' => true,
    ]);
    $farStocked = Branch::factory()->create([
        'latitude' => -33.8688,
        'longitude' => 151.2093,
        'delivery_radius_km' => null,
        'status' => true,
    ]);
    Inventory::factory()->create([
        'branch_id' => $farStocked->id,
        'product_id' => $product->id,
        'quantity' => 5,
        'status' => true,
    ]);

    $plan = app(OrderAllocationEngine::class)->allocate(-32.8688, 151.2093, collect([allocationCartItem($product, 1)]));

    expect($plan['branch']->id)->toBe($farStocked->id)
        ->and($plan['branch']->id)->not->toBe($nearEmpty->id)
        ->and($plan['transfers'])->toHaveCount(0);
});

test('allocation fails when no branch anywhere has enough total stock', function () {
    $product = Product::factory()->create();
    $branch = Branch::factory()->create(['latitude' => -33.8688, 'longitude' => 151.2093, 'status' => true]);
    Inventory::factory()->create(['branch_id' => $branch->id, 'product_id' => $product->id, 'quantity' => 1, 'status' => true]);

    expect(fn () => app(OrderAllocationEngine::class)->allocate(-33.8688, 151.2093, collect([allocationCartItem($product, 5)])))
        ->toThrow(ValidationException::class);
});

test('allocation fails when there is no active branch at all', function () {
    $product = Product::factory()->create();

    expect(fn () => app(OrderAllocationEngine::class)->allocate(-33.8688, 151.2093, collect([allocationCartItem($product, 1)])))
        ->toThrow(ValidationException::class);
});

test('same-day delivery rejects a transfer that exceeds the sla', function () {
    config(['catalog.same_day_max_transfer_hours' => 1, 'catalog.transfer_hours_per_km' => 1, 'catalog.transfer_handling_buffer_hours' => 1]);

    $local = Product::factory()->create();
    $missing = Product::factory()->create();
    $sydney = Branch::factory()->create([
        'latitude' => -33.8688,
        'longitude' => 151.2093,
        'delivery_radius_km' => null,
        'status' => true,
    ]);
    $melbourne = Branch::factory()->create([
        'latitude' => -37.8136,
        'longitude' => 144.9631,
        'delivery_radius_km' => null,
        'status' => true,
    ]);
    Inventory::factory()->create(['branch_id' => $sydney->id, 'product_id' => $local->id, 'quantity' => 1, 'status' => true]);
    Inventory::factory()->create(['branch_id' => $melbourne->id, 'product_id' => $missing->id, 'quantity' => 1, 'status' => true]);

    $items = collect([allocationCartItem($local, 1), allocationCartItem($missing, 1)]);

    expect(fn () => app(OrderAllocationEngine::class)->allocate(-33.8688, 151.2093, $items, 'same-day'))
        ->toThrow(ValidationException::class);
});

test('a cheaper operational transfer wins over a closer expensive transfer', function () {
    config(['catalog.default_transfer_handling_fee' => 0, 'catalog.default_transfer_cost_per_km' => 0]);

    $needed = Product::factory()->create();
    $local = Product::factory()->create();
    $destination = Branch::factory()->create([
        'latitude' => -33.8688,
        'longitude' => 151.2093,
        'delivery_radius_km' => null,
        'status' => true,
        'transfer_handling_fee' => 50,
        'transfer_cost_per_km' => 10,
    ]);
    $cheapSource = Branch::factory()->create([
        'latitude' => -33.9,
        'longitude' => 151.2,
        'delivery_radius_km' => null,
        'status' => true,
        'transfer_handling_fee' => 1,
        'transfer_cost_per_km' => 0,
        'fulfillment_priority' => 50,
    ]);
    Inventory::factory()->create(['branch_id' => $destination->id, 'product_id' => $local->id, 'quantity' => 1, 'status' => true]);
    Inventory::factory()->create(['branch_id' => $cheapSource->id, 'product_id' => $needed->id, 'quantity' => 1, 'status' => true]);

    $plan = app(OrderAllocationEngine::class)->allocate(
        -33.8688,
        151.2093,
        collect([allocationCartItem($local, 1), allocationCartItem($needed, 1)]),
    );

    expect($plan['branch']->id)->toBe($destination->id)
        ->and($plan['transfers'])->toHaveCount(1)
        ->and($plan['transfers'][0]['source_branch_id'])->toBe($cheapSource->id);
});

test('equal candidates break ties with fulfillment priority', function () {
    $product = Product::factory()->create();
    $preferred = Branch::factory()->create([
        'latitude' => -33.8688,
        'longitude' => 151.2093,
        'delivery_radius_km' => null,
        'status' => true,
        'fulfillment_priority' => 1,
    ]);
    $other = Branch::factory()->create([
        'latitude' => -33.8688,
        'longitude' => 151.2093,
        'delivery_radius_km' => null,
        'status' => true,
        'fulfillment_priority' => 80,
    ]);
    Inventory::factory()->create(['branch_id' => $preferred->id, 'product_id' => $product->id, 'quantity' => 5, 'status' => true]);
    Inventory::factory()->create(['branch_id' => $other->id, 'product_id' => $product->id, 'quantity' => 5, 'status' => true]);

    $plan = app(OrderAllocationEngine::class)->allocate(-33.8688, 151.2093, collect([allocationCartItem($product, 1)]));

    expect($plan['branch']->id)->toBe($preferred->id);
});
