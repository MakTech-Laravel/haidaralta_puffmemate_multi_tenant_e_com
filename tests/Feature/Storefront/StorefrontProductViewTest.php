<?php

use App\Models\Product;
use App\Models\ProductViewLog;
use App\Support\Storefront\StorefrontCatalog;
use Inertia\Testing\AssertableInertia as Assert;

test('opening a storefront product increments views once per ip within 24 hours', function () {
    $product = Product::factory()->create([
        'status' => true,
        'views' => 0,
    ]);

    $this->get(route('products.show', ['product' => $product->slug]))
        ->assertOk();

    expect($product->fresh()->views)->toBe(1)
        ->and(ProductViewLog::query()->where('product_id', $product->id)->count())->toBe(1);

    $this->get(route('products.show', ['product' => $product->slug]))
        ->assertOk();

    expect($product->fresh()->views)->toBe(1)
        ->and(ProductViewLog::query()->where('product_id', $product->id)->count())->toBe(1);
});

test('same ip can count again after 24 hours', function () {
    $product = Product::factory()->create([
        'status' => true,
        'views' => 0,
    ]);

    $this->get(route('products.show', ['product' => $product->slug]))
        ->assertOk();

    expect($product->fresh()->views)->toBe(1);

    $this->travel(25)->hours();

    $this->get(route('products.show', ['product' => $product->slug]))
        ->assertOk();

    expect($product->fresh()->views)->toBe(2)
        ->and(ProductViewLog::query()->where('product_id', $product->id)->count())->toBe(2);
});

test('different ips each increment product views', function () {
    $product = Product::factory()->create([
        'status' => true,
        'views' => 0,
    ]);

    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
        ->get(route('products.show', ['product' => $product->slug]))
        ->assertOk();

    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.20'])
        ->get(route('products.show', ['product' => $product->slug]))
        ->assertOk();

    expect($product->fresh()->views)->toBe(2)
        ->and(ProductViewLog::query()->where('product_id', $product->id)->count())->toBe(2);
});

test('prefetching a storefront product does not increment views', function () {
    $product = Product::factory()->create([
        'status' => true,
    ]);
    $product->forceFill(['views' => 4])->save();

    $this->withHeaders(['Purpose' => 'prefetch'])
        ->get(route('products.show', ['product' => $product->slug]))
        ->assertOk();

    expect($product->fresh()->views)->toBe(4)
        ->and(ProductViewLog::query()->count())->toBe(0);
});

test('partial reloads of a storefront product do not increment views', function () {
    $product = Product::factory()->create([
        'status' => true,
        'views' => 0,
    ]);

    $this->get(route('products.show', ['product' => $product->slug]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/product')
            ->reloadOnly('storefront'));

    expect($product->fresh()->views)->toBe(1)
        ->and(ProductViewLog::query()->where('product_id', $product->id)->count())->toBe(1);
});

test('cart count_view uses the same ip throttle as the product page', function () {
    $product = Product::factory()->create([
        'status' => true,
        'views' => 0,
        'selling_price' => 20,
        'regular_price' => 22,
    ]);

    $this->post(route('cart.store'), [
        'product' => $product->slug,
        'quantity' => 1,
        'count_view' => true,
    ])->assertRedirect();

    expect($product->fresh()->views)->toBe(1);

    $this->post(route('cart.store'), [
        'product' => $product->slug,
        'quantity' => 1,
        'count_view' => true,
    ])->assertRedirect();

    expect($product->fresh()->views)->toBe(1);

    $this->get(route('products.show', ['product' => $product->slug]))
        ->assertOk();

    expect($product->fresh()->views)->toBe(1);
});

test('pruning old view logs does not decrease products views', function () {
    $product = Product::factory()->create([
        'status' => true,
        'views' => 5,
    ]);

    ProductViewLog::factory()->create([
        'product_id' => $product->id,
        'ip_address' => '203.0.113.40',
        'viewed_at' => now()->subDays(8),
    ]);
    ProductViewLog::factory()->create([
        'product_id' => $product->id,
        'ip_address' => '203.0.113.41',
        'viewed_at' => now()->subHours(2),
    ]);

    $this->artisan('product-views:prune')
        ->assertSuccessful();

    expect(ProductViewLog::query()->count())->toBe(1)
        ->and($product->fresh()->views)->toBe(5);
});

test('product cards show compact view counts', function () {
    $product = Product::factory()->create([
        'status' => true,
    ]);
    $product->forceFill(['views' => 1200])->save();

    expect(StorefrontCatalog::mapProductCard($product->fresh())['views'])->toBe('1.2k');
});
