<?php

use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\Catalog\ProductService;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);
});

test('platform can enable a platform-created product for a specific branch', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $branch = Branch::query()->where('slug', 'downtown')->firstOrFail();
    $product = app(ProductService::class)->createFromPlatform([
        'category_ids' => [Category::factory()->create()->id],
        'name' => 'Platform Pack',
        'slug' => 'platform-pack',
        'status' => true,
    ]);

    expect($product->isEnabledForBranch($branch->id))->toBeFalse();

    $this->actingAs($admin, 'platform')
        ->put(route('platform.branches.products.enable', [$branch, $product]), [
            'status' => true,
        ])
        ->assertRedirect();

    expect($product->fresh()->isEnabledForBranch($branch->id))->toBeTrue();
});

test('platform can set stock and price for any branch', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $branch = Branch::query()->where('slug', 'uptown')->firstOrFail();
    $product = Product::factory()->create();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.branches.products.inventory', [$branch, $product]), [
            'type' => 'stock_in',
            'quantity' => 12,
            'minimum_stock' => 3,
        ])
        ->assertRedirect();

    $this->actingAs($admin, 'platform')
        ->put(route('platform.branches.products.prices', [$branch, $product]), [
            'selling_price' => 29.5,
            'discount_type' => 'fixed',
            'discount_price' => 24.0,
        ])
        ->assertRedirect();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.branches.products.show', [$branch, $product]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/branches/products/show')
            ->where('product.inventory.quantity', 12)
            ->where('product.price.selling_price', '29.50')
            ->where('product.price.discount_type', 'fixed')
            ->where('product.price.discount_price', '24.00')
            ->has('transactions.data')
            ->has('transactions.meta')
            ->has('transactions.links')
            ->has('transactions.filters')
            ->has('product.variant_inventories')
            ->has('product.variant_prices'));
});

test('platform can set percent discount and clear discount with none', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $branch = Branch::query()->where('slug', 'uptown')->firstOrFail();
    $product = Product::factory()->create();

    $this->actingAs($admin, 'platform')
        ->put(route('platform.branches.products.prices', [$branch, $product]), [
            'selling_price' => 40,
            'discount_type' => 'percent',
            'discount_price' => 15,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-30',
        ])
        ->assertRedirect();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.branches.products.show', [$branch, $product]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('product.price.discount_type', 'percent')
            ->where('product.price.discount_price', '15.00')
            ->where('product.price.start_date', '2026-09-01'));

    $this->actingAs($admin, 'platform')
        ->put(route('platform.branches.products.prices', [$branch, $product]), [
            'selling_price' => 40,
            'discount_type' => 'none',
        ])
        ->assertRedirect();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.branches.products.show', [$branch, $product]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('product.price.discount_type', 'none')
            ->where('product.price.discount_price', null)
            ->where('product.price.start_date', null)
            ->where('product.price.end_date', null));
});

test('platform can filter inventory transactions by date range', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $branch = Branch::query()->where('slug', 'downtown')->firstOrFail();
    $product = Product::factory()->create();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.branches.products.inventory', [$branch, $product]), [
            'type' => 'stock_in',
            'quantity' => 5,
            'minimum_stock' => 1,
        ])
        ->assertRedirect();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.branches.products.show', [
            $branch,
            $product,
            'txn_from' => now()->toDateString(),
            'txn_to' => now()->toDateString(),
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('transactions.meta.total', 1)
            ->where('transactions.filters.from', now()->toDateString())
            ->where('transactions.filters.to', now()->toDateString())
            ->where('transactions.data.0.quantity', 5));

    $this->actingAs($admin, 'platform')
        ->get(route('platform.branches.products.show', [
            $branch,
            $product,
            'txn_from' => now()->subDays(7)->toDateString(),
            'txn_to' => now()->subDays(1)->toDateString(),
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('transactions.meta.total', 0));
});

test('platform can set variant stock and price on branch inventory show', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $branch = Branch::query()->where('slug', 'downtown')->firstOrFail();
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

    $this->actingAs($admin, 'platform')
        ->post(route('platform.branches.products.inventory', [$branch, $product]), [
            'type' => 'stock_in',
            'quantity' => 3,
            'minimum_stock' => 1,
            'variant_id' => $variant->id,
        ])
        ->assertRedirect();

    $this->actingAs($admin, 'platform')
        ->put(route('platform.branches.products.prices', [$branch, $product]), [
            'selling_price' => 9.99,
            'variant_id' => $variant->id,
            'status' => true,
        ])
        ->assertRedirect();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.branches.products.show', [$branch, $product]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/branches/products/show')
            ->where('product.variant_inventories.'.$variant->id.'.quantity', 3)
            ->where('product.variant_prices.'.$variant->id.'.selling_price', '9.99'));
});

test('branch user still cannot soft-delete catalog products after platform inventory ops', function () {
    $product = Product::factory()->create(['slug' => 'still-protected']);
    $staff = User::query()->where('email', 'branch1.superadmin@dev.com')->firstOrFail();

    $this->actingAs($staff, 'branch')
        ->delete(route('platform.products.destroy', $product))
        ->assertRedirect(route('login'));

    expect(Product::query()->where('slug', 'still-protected')->exists())->toBeTrue();
});
