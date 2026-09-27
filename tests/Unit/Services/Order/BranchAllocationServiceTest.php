<?php

use App\Enums\CartStatus;
use App\Models\Branch;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\User;
use App\Services\Order\BranchAllocationService;
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
    $branch = Branch::factory()->create(['latitude' => -33.8688, 'longitude' => 151.2093, 'delivery_radius_km' => 50, 'status' => true]);
    Inventory::factory()->create(['branch_id' => $branch->id, 'product_id' => $product->id, 'quantity' => 10, 'status' => true]);

    $items = collect([allocationCartItem($product, 4)]);

    $groups = app(BranchAllocationService::class)->allocate(-33.8688, 151.2093, $items);

    expect($groups)->toHaveCount(1)
        ->and($groups[0]['branch']->id)->toBe($branch->id)
        ->and($groups[0]['lines'][0]['quantity'])->toBe(4);
});

test('a line is split across branches when the nearest branch only has partial stock', function () {
    $product = Product::factory()->create();

    $near = Branch::factory()->create(['latitude' => -33.8688, 'longitude' => 151.2093, 'delivery_radius_km' => 3000, 'status' => true]);
    $far = Branch::factory()->create(['latitude' => -37.8136, 'longitude' => 144.9631, 'delivery_radius_km' => 3000, 'status' => true]);

    Inventory::factory()->create(['branch_id' => $near->id, 'product_id' => $product->id, 'quantity' => 3, 'status' => true]);
    Inventory::factory()->create(['branch_id' => $far->id, 'product_id' => $product->id, 'quantity' => 10, 'status' => true]);

    $items = collect([allocationCartItem($product, 5)]);

    $groups = app(BranchAllocationService::class)->allocate(-33.8688, 151.2093, $items);

    expect($groups)->toHaveCount(2);

    $byBranch = collect($groups)->keyBy(fn (array $group): int => $group['branch']->id);

    expect($byBranch[$near->id]['lines'][0]['quantity'])->toBe(3)
        ->and($byBranch[$far->id]['lines'][0]['quantity'])->toBe(2);
});

test('allocation fails when no branch anywhere has enough total stock', function () {
    $product = Product::factory()->create();
    $branch = Branch::factory()->create(['latitude' => -33.8688, 'longitude' => 151.2093, 'status' => true]);
    Inventory::factory()->create(['branch_id' => $branch->id, 'product_id' => $product->id, 'quantity' => 1, 'status' => true]);

    $items = collect([allocationCartItem($product, 5)]);

    expect(fn () => app(BranchAllocationService::class)->allocate(-33.8688, 151.2093, $items))
        ->toThrow(ValidationException::class);
});

test('allocation fails when there is no active branch at all', function () {
    $product = Product::factory()->create();
    $items = collect([allocationCartItem($product, 1)]);

    expect(fn () => app(BranchAllocationService::class)->allocate(-33.8688, 151.2093, $items))
        ->toThrow(ValidationException::class);
});
