<?php

use App\Models\Product;
use App\Support\Storefront\StorefrontCatalog;
use Inertia\Testing\AssertableInertia as Assert;

test('opening a storefront product increments views', function () {
    $product = Product::factory()->create([
        'status' => true,
    ]);

    $this->get(route('products.show', ['product' => $product->slug]))
        ->assertOk();

    expect($product->fresh()->views)->toBe(1);

    $this->get(route('products.show', ['product' => $product->slug]))
        ->assertOk();

    expect($product->fresh()->views)->toBe(2);
});

test('prefetching a storefront product does not increment views', function () {
    $product = Product::factory()->create([
        'status' => true,
    ]);
    $product->forceFill(['views' => 4])->save();

    $this->withHeaders(['Purpose' => 'prefetch'])
        ->get(route('products.show', ['product' => $product->slug]))
        ->assertOk();

    expect($product->fresh()->views)->toBe(4);
});

test('partial reloads of a storefront product do not increment views', function () {
    $product = Product::factory()->create([
        'status' => true,
    ]);

    $this->get(route('products.show', ['product' => $product->slug]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/product')
            ->reloadOnly('storefront'));

    expect($product->fresh()->views)->toBe(1);
});

test('product cards show compact view counts', function () {
    $product = Product::factory()->create([
        'status' => true,
    ]);
    $product->forceFill(['views' => 1200])->save();

    expect(StorefrontCatalog::mapProductCard($product->fresh())['views'])->toBe('1.2k');
});
