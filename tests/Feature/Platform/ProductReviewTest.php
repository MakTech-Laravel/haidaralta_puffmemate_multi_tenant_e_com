<?php

use App\Enums\RoleEnum;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\Order;
use App\Models\OrderFulfillment;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use App\Support\PlatformTeamResolver;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);
});

/**
 * @return array{customer: User, product: Product, order: Order, branch: Branch, review: ProductReview}
 */
function platformReviewFixture(array $overrides = []): array
{
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $customer = User::factory()->create([
        'user_type' => UserType::CUSTOMER,
        'name' => $overrides['customer_name'] ?? 'Review Customer',
    ]);
    $product = Product::factory()->create([
        'name' => $overrides['product_name'] ?? 'Review Product',
    ]);
    $order = Order::factory()->for($customer, 'customer')->delivered()->create([
        'order_number' => $overrides['order_number'] ?? 'ORD-REVIEW-001',
    ]);
    OrderFulfillment::factory()->forOrder($order)->delivered()->create([
        'branch_id' => $sydney->id,
    ]);
    $review = ProductReview::factory()->create([
        'user_id' => $customer->id,
        'order_id' => $order->id,
        'product_id' => $product->id,
        'rating' => 5,
        'body' => $overrides['body'] ?? 'Great flavour and fast delivery.',
        'hidden_at' => $overrides['hidden_at'] ?? null,
    ]);

    return [
        'customer' => $customer,
        'product' => $product,
        'order' => $order,
        'branch' => $sydney,
        'review' => $review,
    ];
}

test('platform admin with manage-users can open reviews index', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_ADMIN)->firstOrFail();
    platformReviewFixture();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.reviews.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('platform/reviews/index')
            ->has('table.data', 1)
            ->where('table.data.0.customer_name', 'Review Customer')
            ->where('table.data.0.product_name', 'Review Product')
            ->where('table.data.0.order_number', 'ORD-REVIEW-001')
            ->where('table.data.0.branch_name', Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->value('name'))
            ->where('table.data.0.body', 'Great flavour and fast delivery.')
            ->where('table.data.0.hidden', false)
            ->has('customerOptionsUrl')
            ->has('productOptionsUrl')
            ->has('branchOptionsUrl'));
});

test('guest cannot access platform reviews', function () {
    $this->get(route('platform.reviews.index'))
        ->assertRedirect();
});

test('platform user without manage-users cannot access reviews', function () {
    $user = User::factory()->create([
        'user_type' => UserType::PLATFORM,
        'email_verified_at' => now(),
        'is_active' => true,
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId(PlatformTeamResolver::PLATFORM_TEAM_ID);
    $user->assignRole(RoleEnum::STAFF->value);

    $this->actingAs($user, 'platform')
        ->get(route('platform.reviews.index'))
        ->assertForbidden();
});

test('reviews index search matches body customer product and order number', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_ADMIN)->firstOrFail();
    platformReviewFixture([
        'body' => 'Unique mint ice flavour notes',
        'customer_name' => 'Searchable Customer',
        'product_name' => 'Searchable Stick',
        'order_number' => 'ORD-SEARCH-999',
    ]);
    platformReviewFixture([
        'body' => 'Other review body',
        'customer_name' => 'Other Person',
        'product_name' => 'Other Product',
        'order_number' => 'ORD-OTHER-111',
    ]);

    $this->actingAs($admin, 'platform')
        ->get(route('platform.reviews.index', [
            'filter' => ['search' => 'mint ice'],
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('table.data', 1)
            ->where('table.data.0.body', 'Unique mint ice flavour notes'));

    $this->actingAs($admin, 'platform')
        ->get(route('platform.reviews.index', [
            'filter' => ['search' => 'Searchable Customer'],
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('table.data', 1)
            ->where('table.data.0.customer_name', 'Searchable Customer'));

    $this->actingAs($admin, 'platform')
        ->get(route('platform.reviews.index', [
            'filter' => ['search' => 'ORD-SEARCH-999'],
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('table.data', 1)
            ->where('table.data.0.order_number', 'ORD-SEARCH-999'));
});

test('reviews index filters by customer product branch and order number', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_ADMIN)->firstOrFail();
    $melbourne = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();

    $match = platformReviewFixture([
        'order_number' => 'ORD-FILTER-777',
    ]);

    $otherCustomer = User::factory()->create(['user_type' => UserType::CUSTOMER]);
    $otherProduct = Product::factory()->create();
    $otherOrder = Order::factory()->for($otherCustomer, 'customer')->delivered()->create([
        'order_number' => 'ORD-FILTER-888',
    ]);
    OrderFulfillment::factory()->forOrder($otherOrder)->delivered()->create([
        'branch_id' => $melbourne->id,
    ]);
    ProductReview::factory()->create([
        'user_id' => $otherCustomer->id,
        'order_id' => $otherOrder->id,
        'product_id' => $otherProduct->id,
        'body' => 'Melbourne review',
    ]);

    $this->actingAs($admin, 'platform')
        ->get(route('platform.reviews.index', [
            'filter' => ['user_id' => $match['customer']->id],
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('table.data', 1)
            ->where('table.data.0.id', $match['review']->id));

    $this->actingAs($admin, 'platform')
        ->get(route('platform.reviews.index', [
            'filter' => ['product_id' => $match['product']->id],
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('table.data', 1)
            ->where('table.data.0.id', $match['review']->id));

    $this->actingAs($admin, 'platform')
        ->get(route('platform.reviews.index', [
            'filter' => ['branch_id' => $match['branch']->id],
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('table.data', 1)
            ->where('table.data.0.id', $match['review']->id));

    $this->actingAs($admin, 'platform')
        ->get(route('platform.reviews.index', [
            'filter' => ['order_number' => 'ORD-FILTER-777'],
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('table.data', 1)
            ->where('table.data.0.order_number', 'ORD-FILTER-777'));
});

test('platform admin can hide and unhide a review', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_ADMIN)->firstOrFail();
    $fixture = platformReviewFixture();
    $review = $fixture['review'];

    $this->actingAs($admin, 'platform')
        ->from(route('platform.reviews.index'))
        ->post(route('platform.reviews.hide', $review))
        ->assertRedirect(route('platform.reviews.index'));

    expect($review->fresh()->hidden_at)->not->toBeNull()
        ->and($review->fresh()->isHidden())->toBeTrue();

    $this->actingAs($admin, 'platform')
        ->from(route('platform.reviews.index'))
        ->post(route('platform.reviews.unhide', $review))
        ->assertRedirect(route('platform.reviews.index'));

    expect($review->fresh()->hidden_at)->toBeNull()
        ->and($review->fresh()->isHidden())->toBeFalse();
});

test('storefront product details omit hidden reviews', function () {
    $fixture = platformReviewFixture([
        'body' => 'Visible review body',
    ]);
    $product = $fixture['product'];

    ProductReview::factory()->hidden()->create([
        'user_id' => $fixture['customer']->id,
        'order_id' => Order::factory()->for($fixture['customer'], 'customer')->delivered()->create()->id,
        'product_id' => $product->id,
        'body' => 'Hidden review body',
        'rating' => 1,
    ]);

    $this->get(route('products.show', [
        'product' => $product->slug,
        'tab' => 'reviews',
    ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/product')
            ->where('product.reviewCount', 1)
            ->where('product.rating', 5)
            ->has('product.reviews', 1)
            ->where('product.reviews.0.body', 'Visible review body'));
});
