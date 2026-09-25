<?php

use App\Enums\CartStatus;
use App\Models\Cart;
use App\Models\CartItem;
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

function storefrontCartProduct(array $overrides = []): Product
{
    return Product::factory()->create(array_merge([
        'selling_price' => 20,
        'regular_price' => 22,
        'status' => true,
    ], $overrides));
}

test('guests can add update and remove cart items', function () {
    $product = storefrontCartProduct(['slug' => 'guest-cart-item']);

    $this->post(route('cart.store'), [
        'product' => $product->slug,
        'quantity' => 2,
    ])->assertRedirect();

    $item = CartItem::query()->firstOrFail();

    expect($item->quantity)->toBe(2);

    $this->patch(route('cart.update', $item), [
        'quantity' => 4,
    ])->assertRedirect();

    expect($item->fresh()->quantity)->toBe(4);

    $this->delete(route('cart.destroy', $item))->assertRedirect();

    expect(CartItem::query()->count())->toBe(0);
});

test('guest cart survives refresh and a rejected checkout', function () {
    $product = storefrontCartProduct(['slug' => 'persist-cart-item']);

    $this->post(route('cart.store'), [
        'product' => $product->slug,
        'quantity' => 1,
    ]);

    $this->get(route('cart'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/cart')
            ->where('itemCount', 1)
            ->missing('groups')
            ->missing('branches')
            ->has('items.0', fn (Assert $item) => $item
                ->has('id')
                ->has('name')
                ->has('slug')
                ->missing('branchId')
                ->missing('branchName')
                ->etc()));

    $this->get(route('checkout'))->assertRedirect(route('login'));

    $this->get(route('cart'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('itemCount', 1)
            ->has('items', 1));
});

test('adding to cart does not flash a server toast', function () {
    $product = storefrontCartProduct(['slug' => 'no-flash-cart-item']);

    $this->post(route('cart.store'), [
        'product' => $product->slug,
        'quantity' => 1,
    ])->assertRedirect();

    expect(session('inertia.flash_data.toast'))->toBeNull()
        ->and(CartItem::query()->sum('quantity'))->toBe(1);
});

test('guest checkout redirects to login', function () {
    $this->get(route('checkout'))->assertRedirect(route('login'));
});

test('login merges guest quantities into the existing user cart', function () {
    $user = User::factory()->create();
    $shared = storefrontCartProduct(['slug' => 'shared-cart-item', 'selling_price' => 12]);
    $guestOnly = storefrontCartProduct(['slug' => 'guest-only-item', 'selling_price' => 8]);
    $userOnly = storefrontCartProduct(['slug' => 'user-only-item', 'selling_price' => 9]);

    $userCart = Cart::query()->create([
        'user_id' => $user->id,
        'status' => CartStatus::Active,
    ]);

    CartItem::query()->create([
        'cart_id' => $userCart->id,
        'product_id' => $shared->id,
        'quantity' => 1,
        'price_snapshot' => 12,
    ]);

    CartItem::query()->create([
        'cart_id' => $userCart->id,
        'product_id' => $userOnly->id,
        'quantity' => 2,
        'price_snapshot' => 9,
    ]);

    $this->post(route('cart.store'), [
        'product' => $shared->slug,
        'quantity' => 3,
    ]);
    $this->post(route('cart.store'), [
        'product' => $guestOnly->slug,
        'quantity' => 1,
    ]);

    $this->post(route('login.store'), [
        'phone' => $user->phone,
        'password' => 'password',
    ])->assertRedirect();

    $this->assertAuthenticated('customer');

    $merged = Cart::query()
        ->where('user_id', $user->id)
        ->where('status', CartStatus::Active)
        ->with('items')
        ->firstOrFail();

    expect($merged->items)->toHaveCount(3)
        ->and($merged->items->firstWhere('product_id', $shared->id)?->quantity)->toBe(4)
        ->and($merged->items->firstWhere('product_id', $guestOnly->id)?->quantity)->toBe(1)
        ->and($merged->items->firstWhere('product_id', $userOnly->id)?->quantity)->toBe(2);
});

test('logout leaves the user cart in place', function () {
    $user = User::factory()->create();
    $product = storefrontCartProduct(['slug' => 'logout-cart-item']);

    $this->actingAs($user, 'customer')
        ->post(route('cart.store'), [
            'product' => $product->slug,
            'quantity' => 2,
        ]);

    $this->post(route('logout'))->assertRedirect(route('login'));
    $this->assertGuest('customer');

    expect(
        CartItem::query()
            ->whereHas('cart', fn ($query) => $query
                ->where('user_id', $user->id)
                ->where('status', CartStatus::Active))
            ->sum('quantity')
    )->toBe(2);

    $this->post(route('login.store'), [
        'phone' => $user->phone,
        'password' => 'password',
    ]);

    $this->get(route('cart'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('itemCount', 2));
});
