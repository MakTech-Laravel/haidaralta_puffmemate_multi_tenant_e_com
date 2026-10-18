<?php

use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderFulfillment;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function deliveredOrderItem(User $customer, string $name = 'Marlboro Gold'): OrderItem
{
    $order = Order::factory()->for($customer, 'customer')->delivered()->create([
        'payment_status' => PaymentStatus::Verified,
        'subtotal' => 30,
        'total_amount' => 30,
    ]);

    $fulfillment = OrderFulfillment::factory()->forOrder($order)->delivered()->create();
    $product = Product::factory()->create(['name' => $name]);

    return OrderItem::query()->create([
        'order_id' => $order->id,
        'fulfillment_id' => $fulfillment->id,
        'product_id' => $product->id,
        'variant_id' => null,
        'product_name_snapshot' => $product->name,
        'sku_snapshot' => $product->sku,
        'product_snapshot' => ['name' => $product->name, 'image' => null],
        'quantity' => 1,
        'unit_price' => 30,
        'subtotal' => 30,
    ]);
}

test('guests are redirected from product to review', function () {
    $this->get(route('customer.product-to-review.index'))
        ->assertRedirect(route('login'));
});

test('customers see products from their delivered orders', function () {
    $customer = User::factory()->create();
    $item = deliveredOrderItem($customer);

    $this->actingAs($customer, 'customer')
        ->get(route('customer.product-to-review.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('customer/product-to-review/index')
            ->has('products', 1)
            ->where('products.0.id', (string) $item->id)
            ->where('products.0.name', 'Marlboro Gold')
            ->where('products.0.orderId', $item->order->order_number)
            ->where('products.0.shippingStatus', 'delivered')
            ->where('products.0.reviewed', false)
            ->where('products.0.viewUrl', null)
            ->where('meta.currentPage', 1)
            ->where('meta.lastPage', 1));
});

test('customers do not see pending cancelled or other customers delivered products', function () {
    $customer = User::factory()->create();
    $other = User::factory()->create();

    $pendingOrder = Order::factory()->for($customer, 'customer')->create([
        'status' => OrderStatus::Pending,
        'payment_status' => PaymentStatus::PendingCollection,
    ]);
    $pendingProduct = Product::factory()->create(['name' => 'Pending Stick']);
    OrderItem::query()->create([
        'order_id' => $pendingOrder->id,
        'fulfillment_id' => OrderFulfillment::factory()->forOrder($pendingOrder)->create()->id,
        'product_id' => $pendingProduct->id,
        'variant_id' => null,
        'product_name_snapshot' => $pendingProduct->name,
        'sku_snapshot' => $pendingProduct->sku,
        'product_snapshot' => ['name' => $pendingProduct->name, 'image' => null],
        'quantity' => 1,
        'unit_price' => 10,
        'subtotal' => 10,
    ]);

    $cancelledOrder = Order::factory()->for($customer, 'customer')->cancelled()->create([
        'payment_status' => PaymentStatus::PendingCollection,
    ]);
    $cancelledProduct = Product::factory()->create(['name' => 'Cancelled Stick']);
    OrderItem::query()->create([
        'order_id' => $cancelledOrder->id,
        'fulfillment_id' => OrderFulfillment::factory()
            ->forOrder($cancelledOrder)
            ->status(FulfillmentStatus::Cancelled)
            ->create()
            ->id,
        'product_id' => $cancelledProduct->id,
        'variant_id' => null,
        'product_name_snapshot' => $cancelledProduct->name,
        'sku_snapshot' => $cancelledProduct->sku,
        'product_snapshot' => ['name' => $cancelledProduct->name, 'image' => null],
        'quantity' => 1,
        'unit_price' => 10,
        'subtotal' => 10,
    ]);

    deliveredOrderItem($other, 'Other Gold');

    $this->actingAs($customer, 'customer')
        ->get(route('customer.product-to-review.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('customer/product-to-review/index')
            ->has('products', 0));
});

test('customers with no delivered orders see an empty list', function () {
    $customer = User::factory()->create();

    $this->actingAs($customer, 'customer')
        ->get(route('customer.product-to-review.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('customer/product-to-review/index')
            ->has('products', 0)
            ->where('meta.lastPage', 1));
});

test('guests are redirected from write product review', function () {
    $this->get(route('customer.product-to-review.show', 1))
        ->assertRedirect(route('login'));
});

test('customers can view the write product review page for a delivered item', function () {
    $customer = User::factory()->create();
    $item = deliveredOrderItem($customer);

    $this->actingAs($customer, 'customer')
        ->get(route('customer.product-to-review.show', $item->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('customer/product-to-review/show')
            ->where('product.id', (string) $item->id)
            ->where('product.name', 'Marlboro Gold')
            ->where('product.orderId', $item->order->order_number));
});

test('customers receive not found for unknown or ineligible products to review', function () {
    $customer = User::factory()->create();
    $other = User::factory()->create();
    $foreign = deliveredOrderItem($other, 'Foreign Gold');

    $pendingOrder = Order::factory()->for($customer, 'customer')->create([
        'status' => OrderStatus::Pending,
        'payment_status' => PaymentStatus::PendingCollection,
    ]);
    $pendingProduct = Product::factory()->create(['name' => 'Pending Stick']);
    $pendingItem = OrderItem::query()->create([
        'order_id' => $pendingOrder->id,
        'fulfillment_id' => OrderFulfillment::factory()->forOrder($pendingOrder)->create()->id,
        'product_id' => $pendingProduct->id,
        'variant_id' => null,
        'product_name_snapshot' => $pendingProduct->name,
        'sku_snapshot' => $pendingProduct->sku,
        'product_snapshot' => ['name' => $pendingProduct->name, 'image' => null],
        'quantity' => 1,
        'unit_price' => 10,
        'subtotal' => 10,
    ]);

    $this->actingAs($customer, 'customer');

    $this->get(route('customer.product-to-review.show', 999999))->assertNotFound();
    $this->get(route('customer.product-to-review.show', $foreign->id))->assertNotFound();
    $this->get(route('customer.product-to-review.show', $pendingItem->id))->assertNotFound();
});

test('guests are redirected from store product review', function () {
    $this->post(route('customer.product-to-review.store', 1), [
        'rating' => 5,
        'body' => 'Great pack.',
    ])->assertRedirect(route('login'));
});

test('customers cannot store a review without rating and body', function () {
    $customer = User::factory()->create();
    $item = deliveredOrderItem($customer);

    $this->actingAs($customer, 'customer')
        ->from(route('customer.product-to-review.show', $item->id))
        ->post(route('customer.product-to-review.store', $item->id), [])
        ->assertSessionHasErrors(['rating', 'body']);
});

test('customers can store a review and then see view review on the list', function () {
    $customer = User::factory()->create();
    $item = deliveredOrderItem($customer);
    $item->load('product');
    $body = 'Fast delivery and solid pack quality.';
    $viewUrl = route('products.show', [
        'product' => $item->product->slug,
        'tab' => 'reviews',
    ], false);

    $this->actingAs($customer, 'customer')
        ->post(route('customer.product-to-review.store', $item->id), [
            'rating' => 5,
            'body' => $body,
        ])
        ->assertRedirect(route('customer.product-to-review.index'));

    $this->assertDatabaseHas('product_reviews', [
        'user_id' => $customer->id,
        'order_id' => $item->order_id,
        'product_id' => $item->product_id,
        'rating' => 5,
        'body' => $body,
    ]);

    $this->actingAs($customer, 'customer')
        ->get(route('customer.product-to-review.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('customer/product-to-review/index')
            ->has('products', 1)
            ->where('products.0.reviewed', true)
            ->where('products.0.viewUrl', $viewUrl));
});

test('customers cannot review the same order and product twice', function () {
    $customer = User::factory()->create();
    $item = deliveredOrderItem($customer);

    $this->actingAs($customer, 'customer')
        ->post(route('customer.product-to-review.store', $item->id), [
            'rating' => 4,
            'body' => 'First review.',
        ])
        ->assertRedirect();

    $this->actingAs($customer, 'customer')
        ->from(route('customer.product-to-review.show', $item->id))
        ->post(route('customer.product-to-review.store', $item->id), [
            'rating' => 5,
            'body' => 'Second review.',
        ])
        ->assertSessionHasErrors('body');

    expect(ProductReview::query()->count())->toBe(1);
});

test('a second line of the same product on the same order cannot be reviewed again', function () {
    $customer = User::factory()->create();
    $first = deliveredOrderItem($customer);
    $second = OrderItem::query()->create([
        'order_id' => $first->order_id,
        'fulfillment_id' => $first->fulfillment_id,
        'product_id' => $first->product_id,
        'variant_id' => null,
        'product_name_snapshot' => $first->product_name_snapshot,
        'sku_snapshot' => $first->sku_snapshot,
        'product_snapshot' => $first->product_snapshot,
        'quantity' => 1,
        'unit_price' => 30,
        'subtotal' => 30,
    ]);

    $this->actingAs($customer, 'customer')
        ->post(route('customer.product-to-review.store', $first->id), [
            'rating' => 5,
            'body' => 'Reviewed once for this order.',
        ])
        ->assertRedirect();

    $this->actingAs($customer, 'customer')
        ->from(route('customer.product-to-review.show', $second->id))
        ->post(route('customer.product-to-review.store', $second->id), [
            'rating' => 4,
            'body' => 'Trying again on the other line.',
        ])
        ->assertSessionHasErrors('body');

    $this->actingAs($customer, 'customer')
        ->get(route('customer.product-to-review.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('customer/product-to-review/index')
            ->has('products', 2)
            ->where('products.0.reviewed', true)
            ->where('products.1.reviewed', true));
});

test('customers can review the same product on a different delivered order', function () {
    $customer = User::factory()->create();
    $first = deliveredOrderItem($customer);
    $secondOrder = Order::factory()->for($customer, 'customer')->delivered()->create([
        'payment_status' => PaymentStatus::Verified,
        'subtotal' => 30,
        'total_amount' => 30,
    ]);
    $second = OrderItem::query()->create([
        'order_id' => $secondOrder->id,
        'fulfillment_id' => OrderFulfillment::factory()->forOrder($secondOrder)->delivered()->create()->id,
        'product_id' => $first->product_id,
        'variant_id' => null,
        'product_name_snapshot' => $first->product_name_snapshot,
        'sku_snapshot' => $first->sku_snapshot,
        'product_snapshot' => $first->product_snapshot,
        'quantity' => 1,
        'unit_price' => 30,
        'subtotal' => 30,
    ]);

    $this->actingAs($customer, 'customer');

    $this->post(route('customer.product-to-review.store', $first->id), [
        'rating' => 5,
        'body' => 'First order review.',
    ])->assertRedirect();

    $this->post(route('customer.product-to-review.store', $second->id), [
        'rating' => 4,
        'body' => 'Second order review.',
    ])->assertRedirect();

    expect(ProductReview::query()->count())->toBe(2);
});

test('write product review redirects to the list after a review exists', function () {
    $customer = User::factory()->create();
    $item = deliveredOrderItem($customer);

    ProductReview::factory()->create([
        'user_id' => $customer->id,
        'order_id' => $item->order_id,
        'product_id' => $item->product_id,
        'rating' => 5,
        'body' => 'Already reviewed.',
    ]);

    $this->actingAs($customer, 'customer')
        ->get(route('customer.product-to-review.show', $item->id))
        ->assertRedirect(route('customer.product-to-review.index'));
});

test('storefront product details include saved reviews', function () {
    $customer = User::factory()->create(['name' => 'Hormat Felsal']);
    $item = deliveredOrderItem($customer);
    $item->load('product');

    ProductReview::factory()->create([
        'user_id' => $customer->id,
        'order_id' => $item->order_id,
        'product_id' => $item->product_id,
        'rating' => 5,
        'body' => 'Consistent pack quality and fast local delivery.',
    ]);

    $this->get(route('products.show', [
        'product' => $item->product->slug,
        'tab' => 'reviews',
    ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/product')
            ->where('tab', 'reviews')
            ->where('product.reviewCount', 1)
            ->where('product.rating', 5)
            ->has('product.reviews', 1)
            ->where('product.reviews.0.author', 'Hormat Felsal')
            ->where('product.reviews.0.body', 'Consistent pack quality and fast local delivery.'));
});

test('customers receive not found when storing a review for an ineligible product', function () {
    $customer = User::factory()->create();
    $other = User::factory()->create();
    $foreign = deliveredOrderItem($other, 'Foreign Gold');

    $this->actingAs($customer, 'customer')
        ->post(route('customer.product-to-review.store', $foreign->id), [
            'rating' => 5,
            'body' => 'Not my order.',
        ])
        ->assertNotFound();
});
