<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProductTag;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderFulfillment;
use App\Models\OrderItem;
use App\Models\Product;
use App\Support\Storefront\StorefrontCatalog;
use Database\Seeders\ApplicationSettingsSeeder;
use Database\Seeders\BranchSeeder;
use Database\Seeders\CmsPageSeeder;
use Database\Seeders\HomeBannerSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Records one order line for a product and returns the order.
 */
function railSale(
    Product $product,
    int $quantity,
    OrderStatus $status = OrderStatus::Delivered,
    PaymentStatus $payment = PaymentStatus::Collected,
    bool $removed = false,
): Order {
    $order = Order::factory()->create(['status' => $status, 'payment_status' => $payment]);
    $fulfillment = OrderFulfillment::factory()->forOrder($order)->create();

    OrderItem::query()->create([
        'order_id' => $order->id,
        'fulfillment_id' => $fulfillment->id,
        'product_id' => $product->id,
        'product_name_snapshot' => $product->name,
        'sku_snapshot' => $product->sku,
        'quantity' => $quantity,
        'unit_price' => 10,
        'subtotal' => 10 * $quantity,
        'removed_at' => $removed ? now() : null,
    ]);

    return $order;
}

/**
 * @return list<string>
 */
function bestSellingIds(): array
{
    return collect(StorefrontCatalog::bestSellingProducts())->pluck('id')->all();
}

test('best selling ranks products by total units sold across orders', function () {
    $small = Product::factory()->create(['name' => 'Small Seller', 'slug' => 'small-seller']);
    $big = Product::factory()->create(['name' => 'Big Seller', 'slug' => 'big-seller']);
    Product::factory()->create(['name' => 'Never Sold', 'slug' => 'never-sold']);

    railSale($small, 2);
    railSale($big, 3);
    railSale($big, 4);

    expect(bestSellingIds())->toBe(['big-seller', 'small-seller']);
});

test('best selling breaks ties by product name', function () {
    $zulu = Product::factory()->create(['name' => 'Zulu', 'slug' => 'zulu']);
    $alpha = Product::factory()->create(['name' => 'Alpha', 'slug' => 'alpha']);

    railSale($zulu, 5);
    railSale($alpha, 5);

    expect(bestSellingIds())->toBe(['alpha', 'zulu']);
});

test('best selling counts only delivered orders with collected or verified payment', function (OrderStatus $status, PaymentStatus $payment, bool $counted) {
    $product = Product::factory()->create(['name' => 'Probe', 'slug' => 'probe']);

    railSale($product, 3, $status, $payment);

    expect(bestSellingIds())->toBe($counted ? ['probe'] : []);
})->with([
    'delivered and collected' => [OrderStatus::Delivered, PaymentStatus::Collected, true],
    'delivered and verified' => [OrderStatus::Delivered, PaymentStatus::Verified, true],
    'delivered but payment still pending' => [OrderStatus::Delivered, PaymentStatus::PendingCollection, false],
    'delivered but payment failed' => [OrderStatus::Delivered, PaymentStatus::Failed, false],
    'delivered but payment cancelled' => [OrderStatus::Delivered, PaymentStatus::Cancelled, false],
    'delivered then refunded' => [OrderStatus::Delivered, PaymentStatus::Refunded, false],
    'pending order' => [OrderStatus::Pending, PaymentStatus::PendingCollection, false],
    'placed order' => [OrderStatus::Placed, PaymentStatus::PendingCollection, false],
    'in transit order' => [OrderStatus::OutForDelivery, PaymentStatus::PendingCollection, false],
    'cancelled order' => [OrderStatus::Cancelled, PaymentStatus::Cancelled, false],
    'delivery failed order' => [OrderStatus::DeliveryFailed, PaymentStatus::PendingCollection, false],
    'returned order' => [OrderStatus::Returned, PaymentStatus::PendingCollection, false],
]);

test('best selling ignores order items removed from an order', function () {
    $product = Product::factory()->create(['name' => 'Removed Line', 'slug' => 'removed-line']);

    railSale($product, 4, removed: true);

    expect(bestSellingIds())->toBe([]);
});

test('best selling hides inactive and deleted products', function () {
    $inactive = Product::factory()->create(['name' => 'Inactive', 'slug' => 'inactive', 'status' => false]);
    $deleted = Product::factory()->create(['name' => 'Deleted', 'slug' => 'deleted']);
    $active = Product::factory()->create(['name' => 'Active', 'slug' => 'active']);

    railSale($inactive, 9);
    railSale($deleted, 8);
    railSale($active, 1);
    $deleted->delete();

    expect(bestSellingIds())->toBe(['active']);
});

test('best selling ignores tags and categories', function () {
    $category = Category::factory()->create(['slug' => 'any-category']);

    $untagged = Product::factory()->create(['name' => 'Untagged Seller', 'slug' => 'untagged-seller', 'tags' => []]);
    $tagged = Product::factory()->create([
        'name' => 'Tagged But Unsold',
        'slug' => 'tagged-but-unsold',
        'tags' => [ProductTag::BestSelling->value],
    ]);
    $tagged->categories()->sync([$category->id]);

    railSale($untagged, 2);

    expect(bestSellingIds())->toBe(['untagged-seller']);
});

test('best selling respects the configured home product limit', function () {
    config(['catalog.home_products_max' => 2]);

    foreach (['one' => 5, 'two' => 4, 'three' => 3] as $slug => $quantity) {
        railSale(Product::factory()->create(['name' => ucfirst($slug), 'slug' => $slug]), $quantity);
    }

    expect(bestSellingIds())->toBe(['one', 'two']);
});

test('new arrivals list only active new_arrival products, newest first, regardless of sales or category', function () {
    $oldest = Product::factory()->create([
        'name' => 'Oldest Arrival',
        'slug' => 'oldest-arrival',
        'tags' => [ProductTag::NewArrival->value],
        'created_at' => now()->subDays(10),
    ]);
    Product::factory()->create([
        'name' => 'Newest Arrival',
        'slug' => 'newest-arrival',
        'tags' => [ProductTag::NewArrival->value],
        'created_at' => now()->subDay(),
    ]);
    Product::factory()->create([
        'name' => 'Middle Arrival',
        'slug' => 'middle-arrival',
        'tags' => [ProductTag::BestSelling->value, ProductTag::NewArrival->value],
        'created_at' => now()->subDays(5),
    ]);
    Product::factory()->create([
        'name' => 'Inactive Arrival',
        'slug' => 'inactive-arrival',
        'status' => false,
        'tags' => [ProductTag::NewArrival->value],
        'created_at' => now(),
    ]);
    Product::factory()->create([
        'name' => 'Not Tagged',
        'slug' => 'not-tagged',
        'tags' => [ProductTag::BestSelling->value],
        'created_at' => now(),
    ]);

    railSale($oldest, 50);

    $ids = collect(StorefrontCatalog::productsWithTag(ProductTag::NewArrival))->pluck('id')->all();

    expect($ids)->toBe(['newest-arrival', 'middle-arrival', 'oldest-arrival']);
});

test('new arrivals respect the configured home product limit', function () {
    config(['catalog.home_products_max' => 2]);

    foreach (range(1, 4) as $index) {
        Product::factory()->create([
            'name' => "Arrival {$index}",
            'slug' => "arrival-{$index}",
            'tags' => [ProductTag::NewArrival->value],
            'created_at' => now()->subDays(10 - $index),
        ]);
    }

    $ids = collect(StorefrontCatalog::productsWithTag(ProductTag::NewArrival))->pluck('id')->all();

    expect($ids)->toBe(['arrival-4', 'arrival-3']);
});

test('the home page serves both rails with product cards', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);
    $this->seed(ApplicationSettingsSeeder::class);
    $this->seed(CmsPageSeeder::class);
    $this->seed(HomeBannerSeeder::class);

    $seller = Product::factory()->create([
        'name' => 'Rail Seller',
        'slug' => 'rail-seller',
        'selling_price' => 14.00,
        'pack_label' => 'Gold Pack',
        'pieces_per_pack' => 20,
        'pack_unit' => 'Cigarettes',
        'tags' => [],
    ]);
    Product::factory()->create([
        'name' => 'Rail Arrival',
        'slug' => 'rail-arrival',
        'selling_price' => 9.90,
        'tags' => [ProductTag::NewArrival->value],
    ]);

    railSale($seller, 3);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/home')
            ->has('bestSellingProducts', 1)
            ->where('bestSellingProducts.0.id', 'rail-seller')
            ->where('bestSellingProducts.0.name', 'Rail Seller')
            ->where('bestSellingProducts.0.description', 'Gold Pack • 20 Cigarettes')
            ->where('bestSellingProducts.0.price', 'A$14.00')
            ->where('bestSellingProducts.0', fn ($card): bool => ! array_key_exists('stock', collect($card)->all()))
            ->has('newArrivalProducts', 1)
            ->where('newArrivalProducts.0.id', 'rail-arrival')
            ->where('newArrivalProducts.0.price', 'A$9.90'));
});
