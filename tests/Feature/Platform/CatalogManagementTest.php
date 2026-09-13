<?php

use App\Models\Branch;
use App\Models\BranchProduct;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);
});

test('platform admin can create a category and product without enabling branches', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $branch = Branch::query()->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.categories.store'), [
            'name' => 'Devices',
            'slug' => 'devices',
            'status' => true,
        ])
        ->assertRedirect(route('platform.categories.show', 'devices'))
        ->assertInertiaFlash('toast', [
            'type' => 'success',
            'message' => 'Category created.',
        ]);

    $category = Category::query()->where('slug', 'devices')->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.products.store'), [
            'category_id' => $category->id,
            'name' => 'Starter Kit',
            'slug' => 'starter-kit',
            'sku' => 'SKU-START',
            'status' => true,
        ])
        ->assertRedirect(route('platform.products.show', 'starter-kit'))
        ->assertInertiaFlash('toast', [
            'type' => 'success',
            'message' => 'Product created. Disabled for all branches — enable from branch inventory.',
        ]);

    $product = Product::query()->where('slug', 'starter-kit')->firstOrFail();

    expect($product->isEnabledForBranch($branch->id))->toBeFalse()
        ->and(BranchProduct::query()->where('product_id', $product->id)->count())->toBe(0);
});

test('platform soft delete hides product from default index', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $product = Product::factory()->create(['name' => 'Hidden Pack', 'slug' => 'hidden-pack']);

    $this->actingAs($admin, 'platform')
        ->delete(route('platform.products.destroy', $product))
        ->assertRedirect(route('platform.products.index'));

    expect(Product::query()->where('slug', 'hidden-pack')->exists())->toBeFalse()
        ->and(Product::withTrashed()->where('slug', 'hidden-pack')->exists())->toBeTrue();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.products.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/catalog/products/index')
            ->has('table.data', 0));
});

test('platform can restore a soft-deleted product', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $product = Product::factory()->create(['slug' => 'restorable-pack']);
    $product->delete();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.products.restore', 'restorable-pack'))
        ->assertRedirect(route('platform.products.show', 'restorable-pack'));

    expect(Product::query()->where('slug', 'restorable-pack')->exists())->toBeTrue();
});

test('platform product suggestions match existing catalog items', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $match = Product::factory()->create([
        'name' => 'Air Pro Disposable',
        'slug' => 'air-pro-disposable',
        'sku' => 'POM-AIR-PRO',
    ]);
    $other = Product::factory()->create([
        'name' => 'Berry Mist Pod',
        'slug' => 'berry-mist-pod',
    ]);

    $this->actingAs($admin, 'platform')
        ->getJson(route('platform.products.suggestions', ['q' => 'Air']))
        ->assertOk()
        ->assertJsonPath('suggestions.0.id', $match->id)
        ->assertJsonMissing(['id' => $other->id]);

    $this->actingAs($admin, 'platform')
        ->getJson(route('platform.products.suggestions', [
            'q' => 'Air',
            'exclude' => $match->id,
        ]))
        ->assertOk()
        ->assertJsonPath('suggestions', []);
});

test('platform categories data table filters sorts and paginates', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    $parent = Category::factory()->create(['name' => 'Parent DT', 'slug' => 'parent-dt']);
    $active = Category::factory()->create([
        'name' => 'Alpha Category',
        'slug' => 'alpha-category',
        'parent_id' => $parent->id,
        'status' => true,
    ]);
    Category::factory()->create([
        'name' => 'Beta Inactive',
        'slug' => 'beta-inactive-cat',
        'status' => false,
    ]);
    $trashed = Category::factory()->create([
        'name' => 'Zulu Trashed',
        'slug' => 'zulu-trashed-cat',
        'status' => true,
    ]);
    $trashed->delete();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.categories.index', [
            'filter' => [
                'status' => 'active',
                'parent_id' => $parent->id,
            ],
            'sort' => 'name',
            'per_page' => 25,
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/catalog/categories/index')
            ->has('table.data', 1)
            ->where('table.data.0.id', $active->id)
            ->where('table.per_page', 25)
            ->has('table.filterOptions.parents'));

    $this->actingAs($admin, 'platform')
        ->get(route('platform.categories.index', [
            'filter' => ['trashed' => 'only'],
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('table.data', 1)
            ->where('table.data.0.slug', 'zulu-trashed-cat'));
});

test('platform products data table filters sorts and paginates', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $fruit = Category::factory()->create(['name' => 'Fruit', 'slug' => 'fruit-dt']);
    $gear = Category::factory()->create(['name' => 'Gear', 'slug' => 'gear-dt']);

    $active = Product::factory()->create([
        'category_id' => $fruit->id,
        'name' => 'Alpha Active',
        'slug' => 'alpha-active',
        'status' => true,
    ]);
    Product::factory()->create([
        'category_id' => $gear->id,
        'name' => 'Beta Inactive',
        'slug' => 'beta-inactive',
        'status' => false,
    ]);
    $trashed = Product::factory()->create([
        'category_id' => $fruit->id,
        'name' => 'Zulu Trashed',
        'slug' => 'zulu-trashed',
        'status' => true,
    ]);
    $trashed->delete();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.products.index', [
            'filter' => [
                'status' => 'active',
                'category_id' => $fruit->id,
            ],
            'sort' => 'name',
            'per_page' => 25,
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/catalog/products/index')
            ->has('table.data', 1)
            ->where('table.data.0.id', $active->id)
            ->where('table.data.0.variants_count', 0)
            ->where('table.per_page', 25)
            ->has('table.filterOptions.categories'));

    $this->actingAs($admin, 'platform')
        ->get(route('platform.products.index', [
            'filter' => ['trashed' => 'only'],
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('table.data', 1)
            ->where('table.data.0.slug', 'zulu-trashed'));
});

test('platform categories data table filters by status', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $active = Category::factory()->create([
        'name' => 'Active Cat',
        'slug' => 'active-cat-dt',
        'status' => true,
    ]);
    Category::factory()->create([
        'name' => 'Inactive Cat',
        'slug' => 'inactive-cat-dt',
        'status' => false,
    ]);

    $this->actingAs($admin, 'platform')
        ->get(route('platform.categories.index', [
            'filter' => [
                'status' => 'active',
                'search' => 'Active Cat',
            ],
            'sort' => 'name',
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/catalog/categories/index')
            ->has('table.data', 1)
            ->where('table.data.0.id', $active->id)
            ->where('table.data.0.products_count', 0));
});
