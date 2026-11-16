<?php

use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductPackage;
use App\Services\Application\PaymentGatewaySettingsService;
use App\Services\Catalog\DeliverySettingsService;
use App\Support\Storefront\StorefrontCatalog;
use Database\Seeders\ApplicationSettingsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(ApplicationSettingsSeeder::class);
    app(PaymentGatewaySettingsService::class)->ensureDefaults();
    app(DeliverySettingsService::class)->ensureDefaults();
    config(['catalog.show_product_packages' => true]);
});

test('cart payload includes package label and package price', function () {
    $product = Product::factory()->create([
        'slug' => 'pkg-cart-payload',
        'selling_price' => 100,
        'status' => true,
    ]);
    $package = ProductPackage::factory()->create([
        'product_id' => $product->id,
        'label' => 'Pack of 2',
        'quantity' => 2,
        'price' => 160,
        'status' => true,
    ]);

    $this->post(route('cart.store'), [
        'product' => $product->slug,
        'quantity' => 1,
        'product_package_id' => $package->id,
    ])->assertRedirect();

    $this->get(route('cart'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/cart')
            ->has('items', 1)
            ->where('items.0.productPackageId', $package->id)
            ->where('items.0.packageLabel', 'Pack of 2')
            ->where('items.0.packageQuantity', 2)
            ->where('items.0.unitPrice', 160)
            ->etc());
});

test('storefront omits packages when kill switch is off', function () {
    config(['catalog.show_product_packages' => false]);

    $product = Product::factory()->create([
        'slug' => 'pkg-hidden-switch',
        'status' => true,
    ]);
    ProductPackage::factory()->create([
        'product_id' => $product->id,
        'status' => true,
    ]);

    $detail = StorefrontCatalog::productDetail($product);

    expect($detail['packages'])->toBe([]);
});

test('product cards list enabled packages and hide disabled ones', function () {
    $product = Product::factory()->create([
        'slug' => 'card-with-packages',
        'packages_enabled' => true,
        'status' => true,
        'selling_price' => 120,
    ]);
    $package = ProductPackage::factory()->create([
        'product_id' => $product->id,
        'label' => '10 Items',
        'quantity' => 10,
        'price' => 1000,
        'status' => true,
        'sort_order' => 1,
    ]);
    ProductPackage::factory()->disabled()->create([
        'product_id' => $product->id,
        'label' => 'Hidden',
        'price' => 1,
    ]);

    $card = StorefrontCatalog::mapProductCard($product->fresh());

    expect($card['packages'])->toHaveCount(1)
        ->and($card['packages'][0])->toMatchArray([
            'id' => $package->id,
            'label' => '10 Items',
            'quantity' => 10,
            'formattedPrice' => 'A$1,000.00',
        ]);
});

test('product cards omit packages when the kill switch is off', function () {
    config(['catalog.show_product_packages' => false]);

    $product = Product::factory()->create([
        'slug' => 'card-packages-hidden',
        'packages_enabled' => true,
        'status' => true,
    ]);
    ProductPackage::factory()->create([
        'product_id' => $product->id,
        'status' => true,
    ]);

    expect(StorefrontCatalog::mapProductCard($product->fresh())['packages'])->toBe([]);
});

test('products without packages still add with catalog price', function () {
    $product = Product::factory()->create([
        'slug' => 'pkg-none',
        'selling_price' => 42.5,
        'status' => true,
    ]);

    $this->post(route('cart.store'), [
        'product' => $product->slug,
        'quantity' => 3,
    ])->assertRedirect();

    $item = CartItem::query()->firstOrFail();

    expect($item->product_package_id)->toBeNull()
        ->and((float) $item->price_snapshot)->toBe(42.5)
        ->and($item->quantity)->toBe(3);
});
