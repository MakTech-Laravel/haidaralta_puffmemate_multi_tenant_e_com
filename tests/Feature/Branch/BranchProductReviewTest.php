<?php

use App\Enums\UserType;
use App\Models\Branch;
use App\Models\Order;
use App\Models\OrderFulfillment;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);
});

/**
 * @return array{customer: User, product: Product, order: Order, branch: Branch, review: ProductReview}
 */
function branchReviewFixture(Branch $branch, array $overrides = []): array
{
    $customer = User::factory()->create([
        'user_type' => UserType::CUSTOMER,
        'name' => $overrides['customer_name'] ?? 'Branch Review Customer',
    ]);
    $product = Product::factory()->create([
        'name' => $overrides['product_name'] ?? 'Branch Review Product',
    ]);
    $order = Order::factory()->for($customer, 'customer')->delivered()->create([
        'order_number' => $overrides['order_number'] ?? 'ORD-BRANCH-REVIEW-001',
    ]);
    OrderFulfillment::factory()->forOrder($order)->delivered()->create([
        'branch_id' => $branch->id,
    ]);
    $review = ProductReview::factory()->create([
        'user_id' => $customer->id,
        'order_id' => $order->id,
        'product_id' => $product->id,
        'rating' => 5,
        'body' => $overrides['body'] ?? 'Delivered fresh from this branch.',
        'hidden_at' => $overrides['hidden_at'] ?? null,
    ]);

    return [
        'customer' => $customer,
        'product' => $product,
        'order' => $order,
        'branch' => $branch,
        'review' => $review,
    ];
}

test('guests are redirected from branch reviews', function () {
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();

    $this->get(route('branch.reviews.index', $sydney))
        ->assertRedirect(route('login'));
});

test('branch staff see only reviews for orders fulfilled at their branch', function () {
    $admin = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $other = Branch::factory()->create();

    $mine = branchReviewFixture($sydney, [
        'body' => 'Sydney fulfillment review',
        'order_number' => 'ORD-SYD-REV-1',
    ]);
    branchReviewFixture($other, [
        'body' => 'Other branch review',
        'order_number' => 'ORD-OTHER-REV-1',
    ]);

    $this->actingAs($admin, 'branch')
        ->get(route('branch.reviews.index', $sydney))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('branch/reviews/index')
            ->has('table.data', 1)
            ->where('table.data.0.id', $mine['review']->id)
            ->where('table.data.0.body', 'Sydney fulfillment review')
            ->where('table.data.0.order_number', 'ORD-SYD-REV-1')
            ->has('customerOptionsUrl')
            ->has('productOptionsUrl'));
});

test('branch staff can view a review fulfilled at their branch', function () {
    $admin = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $fixture = branchReviewFixture($sydney);

    $this->actingAs($admin, 'branch')
        ->get(route('branch.reviews.show', [$sydney, $fixture['review']]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('branch/reviews/show')
            ->where('review.id', $fixture['review']->id)
            ->where('review.body', 'Delivered fresh from this branch.')
            ->where('review.customer.name', $fixture['customer']->name)
            ->where('review.product.name', $fixture['product']->name)
            ->where('review.order.order_number', $fixture['order']->order_number));
});

test('branch staff cannot view a review fulfilled at another branch', function () {
    $admin = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $other = Branch::factory()->create();
    $fixture = branchReviewFixture($other);

    $this->actingAs($admin, 'branch')
        ->get(route('branch.reviews.show', [$sydney, $fixture['review']]))
        ->assertNotFound();
});

test('branch staff can hide and unhide a review at their branch', function () {
    $admin = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $fixture = branchReviewFixture($sydney);
    $review = $fixture['review'];

    $this->actingAs($admin, 'branch')
        ->from(route('branch.reviews.index', $sydney))
        ->post(route('branch.reviews.hide', [$sydney, $review]))
        ->assertRedirect(route('branch.reviews.index', $sydney));

    expect($review->fresh()->hidden_at)->not->toBeNull()
        ->and($review->fresh()->isHidden())->toBeTrue();

    $this->actingAs($admin, 'branch')
        ->from(route('branch.reviews.index', $sydney))
        ->post(route('branch.reviews.unhide', [$sydney, $review]))
        ->assertRedirect(route('branch.reviews.index', $sydney));

    expect($review->fresh()->hidden_at)->toBeNull()
        ->and($review->fresh()->isHidden())->toBeFalse();
});

test('branch staff cannot hide a review fulfilled at another branch', function () {
    $admin = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $other = Branch::factory()->create();
    $fixture = branchReviewFixture($other);

    $this->actingAs($admin, 'branch')
        ->post(route('branch.reviews.hide', [$sydney, $fixture['review']]))
        ->assertNotFound();

    expect($fixture['review']->fresh()->hidden_at)->toBeNull();
});
