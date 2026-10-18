<?php

use App\Models\Branch;
use App\Models\BranchProductPrice;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Support\BranchProductPriceDisplay;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);
});

test('platform product store persists catalog selling and regular prices when branch pricing is hidden', function () {
    config(['catalog.show_branch_pricing' => false]);

    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $category = Category::factory()->create();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.products.store'), [
            'category_ids' => [$category->id],
            'name' => 'Catalog Priced Pack',
            'slug' => 'catalog-priced-pack',
            'sku' => 'CAT-PRICE-1',
            'selling_price' => 24.5,
            'regular_price' => 29.99,
            'status' => true,
        ])
        ->assertRedirect();

    $product = Product::query()->where('slug', 'catalog-priced-pack')->firstOrFail();

    expect((float) $product->selling_price)->toBe(24.5)
        ->and((float) $product->regular_price)->toBe(29.99)
        ->and(BranchProductPrice::query()->where('product_id', $product->id)->exists())->toBeFalse();
});

test('platform product update changes catalog prices when branch pricing is hidden', function () {
    config(['catalog.show_branch_pricing' => false]);

    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $product = Product::factory()->create([
        'slug' => 'edit-catalog-price',
        'selling_price' => 10,
        'regular_price' => 12,
    ]);

    $this->actingAs($admin, 'platform')
        ->put(route('platform.products.update', $product), [
            'category_ids' => [$product->categories()->firstOrFail()->id],
            'name' => $product->name,
            'slug' => $product->slug,
            'sku' => $product->sku,
            'selling_price' => 18.75,
            'regular_price' => 22,
            'status' => true,
        ])
        ->assertRedirect(route('platform.products.show', $product));

    $product->refresh();

    expect((float) $product->selling_price)->toBe(18.75)
        ->and((float) $product->regular_price)->toBe(22.0);
});

test('branch product create writes catalog prices and stock without branch price rows when branch pricing is hidden', function () {
    config([
        'catalog.show_branch_pricing' => false,
        'catalog.branch_can_manage_catalog_pricing' => true,
    ]);

    $staff = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $branch = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $category = Category::factory()->create();

    $this->actingAs($staff, 'branch')
        ->post(route('branch.products.store', $branch), [
            'category_ids' => [$category->id],
            'name' => 'Branch Catalog Price',
            'slug' => 'branch-catalog-price',
            'sku' => 'BCP-1',
            'status' => true,
            'selling_price' => 15.25,
            'regular_price' => 17.5,
            'quantity' => 3,
        ])
        ->assertRedirect();

    $product = Product::query()->where('slug', 'branch-catalog-price')->firstOrFail();

    expect((float) $product->selling_price)->toBe(15.25)
        ->and((float) $product->regular_price)->toBe(17.5)
        ->and(BranchProductPrice::query()->where('product_id', $product->id)->exists())->toBeFalse()
        ->and($product->inventories()->where('branch_id', $branch->id)->value('quantity'))->toBe(3);
});

test('price upsert and price history abort while branch pricing is hidden', function () {
    config(['catalog.show_branch_pricing' => false]);

    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $staff = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $branch = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $product = Product::factory()->create();

    $this->actingAs($admin, 'platform')
        ->put(route('platform.branches.products.prices', [$branch, $product]), [
            'selling_price' => 9.99,
            'discount_type' => 'none',
        ])
        ->assertNotFound();

    $this->actingAs($staff, 'branch')
        ->put(route('branch.products.prices', [$branch, $product]), [
            'selling_price' => 9.99,
            'discount_type' => 'none',
        ])
        ->assertNotFound();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.reports.price-history'))
        ->assertNotFound();

    $this->actingAs($staff, 'branch')
        ->get(route('branch.reports.price-history', $branch))
        ->assertNotFound();
});

test('price display uses product catalog columns when branch pricing is hidden', function () {
    config(['catalog.show_branch_pricing' => false]);

    $branch = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $product = Product::factory()->create([
        'selling_price' => 19.99,
        'regular_price' => 24.99,
    ]);

    BranchProductPrice::query()->create([
        'branch_id' => $branch->id,
        'product_id' => $product->id,
        'selling_price' => 99.99,
        'regular_price' => 120,
        'discount_type' => 'none',
        'status' => true,
    ]);

    $display = BranchProductPriceDisplay::forBranchProduct($branch, $product);
    $list = BranchProductPriceDisplay::listDisplayForProductContext(null, $product);

    expect($display['amount'])->toBe(19.99)
        ->and($display['compare_at'])->toBe(24.99)
        ->and($display['label'])->toBe('A$19.99')
        ->and($list['selling_price'])->toBe('19.99')
        ->and($list['has_compare_at'])->toBeTrue();
});

test('price upsert succeeds when branch pricing is enabled', function () {
    config(['catalog.show_branch_pricing' => true]);

    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $branch = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $product = Product::factory()->create();

    $this->actingAs($admin, 'platform')
        ->from(route('platform.stock.manage', [
            'branch' => $branch->id,
            'product' => $product->id,
        ]))
        ->put(route('platform.branches.products.prices', [$branch, $product]), [
            'selling_price' => 12.5,
            'discount_type' => 'none',
        ])
        ->assertRedirect();

    expect(BranchProductPrice::query()
        ->where('branch_id', $branch->id)
        ->where('product_id', $product->id)
        ->where('selling_price', 12.5)
        ->exists())->toBeTrue();
});
