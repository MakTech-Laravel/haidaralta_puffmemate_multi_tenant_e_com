<?php

use App\Models\Branch;
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

test('platform admin can create a category and product for the shared catalog', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.categories.store'), [
            'name' => 'Devices',
            'slug' => 'devices',
            'status' => true,
            'featured' => true,
        ])
        ->assertRedirect(route('platform.categories.show', 'devices'))
        ->assertInertiaFlash('toast', [
            'type' => 'success',
            'message' => 'Category created.',
        ]);

    $category = Category::query()->where('slug', 'devices')->firstOrFail();

    expect($category->featured)->toBeTrue();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.categories.show', $category))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/catalog/categories/show')
            ->where('category.featured', true));

    $this->actingAs($admin, 'platform')
        ->post(route('platform.products.store'), [
            'category_ids' => [$category->id],
            'name' => 'Starter Kit',
            'slug' => 'starter-kit',
            'sku' => 'SKU-START',
            'status' => true,
        ])
        ->assertRedirect(route('platform.products.show', 'starter-kit'))
        ->assertInertiaFlash('toast', [
            'type' => 'success',
            'message' => 'Product created.',
        ]);

    $product = Product::query()->where('slug', 'starter-kit')->firstOrFail();

    expect($product->categories()->pluck('categories.id')->all())->toBe([$category->id]);
});

test('platform can toggle category featured on update', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $category = Category::factory()->create([
        'slug' => 'featured-toggle',
        'featured' => false,
    ]);

    $this->actingAs($admin, 'platform')
        ->put(route('platform.categories.update', $category), [
            'name' => $category->name,
            'slug' => $category->slug,
            'status' => true,
            'featured' => true,
        ])
        ->assertRedirect(route('platform.categories.show', $category));

    expect($category->fresh()->featured)->toBeTrue();

    $this->actingAs($admin, 'platform')
        ->put(route('platform.categories.update', $category), [
            'name' => $category->name,
            'slug' => $category->slug,
            'status' => true,
            'featured' => false,
        ])
        ->assertRedirect(route('platform.categories.show', $category));

    expect($category->fresh()->featured)->toBeFalse();
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
    $mesh = Product::factory()->create([
        'name' => 'Air Pro Mesh Coils',
        'slug' => 'air-pro-mesh-coils',
        'sku' => 'POM-AIR-MESH',
    ]);
    $other = Product::factory()->create([
        'name' => 'Berry Mist Pod',
        'slug' => 'berry-mist-pod',
    ]);

    $this->actingAs($admin, 'platform')
        ->getJson(route('platform.products.suggestions', ['q' => 'Air']))
        ->assertOk()
        ->assertJsonFragment(['id' => $match->id])
        ->assertJsonFragment(['id' => $mesh->id])
        ->assertJsonMissing(['id' => $other->id]);

    $this->actingAs($admin, 'platform')
        ->getJson(route('platform.products.suggestions', ['q' => 'air pro']))
        ->assertOk()
        ->assertJsonFragment(['id' => $match->id])
        ->assertJsonFragment(['id' => $mesh->id])
        ->assertJsonMissing(['id' => $other->id]);

    $this->actingAs($admin, 'platform')
        ->getJson(route('platform.products.suggestions', [
            'q' => 'Air',
            'exclude' => $match->id,
        ]))
        ->assertOk()
        ->assertJsonMissing(['id' => $match->id])
        ->assertJsonFragment(['id' => $mesh->id]);
});

test('platform category suggestions match existing catalog items', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $match = Category::factory()->create([
        'name' => 'Air Devices',
        'slug' => 'air-devices',
    ]);
    $other = Category::factory()->create([
        'name' => 'Berry Pods',
        'slug' => 'berry-pods',
    ]);

    $this->actingAs($admin, 'platform')
        ->getJson(route('platform.categories.suggestions', ['q' => 'Air']))
        ->assertOk()
        ->assertJsonFragment(['id' => $match->id])
        ->assertJsonMissing(['id' => $other->id]);

    $this->actingAs($admin, 'platform')
        ->getJson(route('platform.categories.suggestions', [
            'q' => 'Air',
            'exclude' => $match->id,
        ]))
        ->assertOk()
        ->assertJsonPath('suggestions', []);
});

test('platform category options paginate and search', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $match = Category::factory()->create([
        'name' => 'Omega Coils',
        'slug' => 'omega-coils-options',
    ]);
    Category::factory()->create([
        'name' => 'Zeta Batteries',
        'slug' => 'zeta-batteries-options',
    ]);

    $this->actingAs($admin, 'platform')
        ->getJson(route('platform.categories.options', [
            'filter' => ['search' => 'Omega'],
            'per_page' => 10,
        ]))
        ->assertOk()
        ->assertJsonFragment(['id' => $match->id, 'name' => 'Omega Coils'])
        ->assertJsonMissing(['slug' => 'zeta-batteries-options'])
        ->assertJsonPath('meta.per_page', 10);
});

test('platform products index accepts multiple category_id filters', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $fruit = Category::factory()->create(['name' => 'Multi Fruit', 'slug' => 'multi-fruit']);
    $gear = Category::factory()->create(['name' => 'Multi Gear', 'slug' => 'multi-gear']);
    $other = Category::factory()->create(['name' => 'Multi Other', 'slug' => 'multi-other']);

    $fruitProduct = Product::factory()->hasAttached($fruit)->create([
        'slug' => 'multi-fruit-pack',
        'status' => true,
    ]);
    $gearProduct = Product::factory()->hasAttached($gear)->create([
        'slug' => 'multi-gear-pack',
        'status' => true,
    ]);
    Product::factory()->hasAttached($other)->create([
        'slug' => 'multi-other-pack',
        'status' => true,
    ]);

    $this->actingAs($admin, 'platform')
        ->get(route('platform.products.index', [
            'filter' => [
                'category_id' => [(string) $fruit->id, (string) $gear->id],
            ],
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/catalog/products/index')
            ->where('table.data', fn ($rows) => collect($rows)->contains(
                fn ($row) => $row['slug'] === $fruitProduct->slug,
            ))
            ->where('table.data', fn ($rows) => collect($rows)->contains(
                fn ($row) => $row['slug'] === $gearProduct->slug,
            ))
            ->where('table.data', fn ($rows) => collect($rows)->every(
                fn ($row) => $row['slug'] !== 'multi-other-pack',
            ))
            ->where('table.filterOptions.categories', fn ($categories) => collect($categories)
                ->pluck('id')
                ->sort()
                ->values()
                ->all() === collect([$fruit->id, $gear->id])->sort()->values()->all()));
});

test('precognitive platform category store returns 422 for a taken slug', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $existing = Category::factory()->create(['slug' => 'taken-category']);
    $before = Category::query()->count();

    $this->actingAs($admin, 'platform')
        ->withHeaders([
            'Precognition' => 'true',
            'Precognition-Validate-Only' => 'slug',
            'Accept' => 'application/json',
        ])
        ->post(route('platform.categories.store'), [
            'slug' => $existing->slug,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['slug'])
        ->assertHeader('Precognition', 'true');

    expect(Category::query()->count())->toBe($before);
});

test('precognitive platform product store returns 422 for a taken slug', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $existing = Product::factory()->create(['slug' => 'taken-product']);
    $before = Product::query()->count();

    $this->actingAs($admin, 'platform')
        ->withHeaders([
            'Precognition' => 'true',
            'Precognition-Validate-Only' => 'slug',
            'Accept' => 'application/json',
        ])
        ->post(route('platform.products.store'), [
            'slug' => $existing->slug,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['slug'])
        ->assertHeader('Precognition', 'true');

    expect(Product::query()->count())->toBe($before);
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

    $active = Product::factory()->hasAttached($fruit)->create([
        'name' => 'Alpha Active',
        'slug' => 'alpha-active',
        'status' => true,
    ]);
    Product::factory()->hasAttached($gear)->create([
        'name' => 'Beta Inactive',
        'slug' => 'beta-inactive',
        'status' => false,
    ]);
    $trashed = Product::factory()->hasAttached($fruit)->create([
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
            ->where('table.filterOptions.categories.0.id', $fruit->id));

    $this->actingAs($admin, 'platform')
        ->get(route('platform.products.index', [
            'filter' => ['trashed' => 'only'],
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('table.data', 1)
            ->where('table.data.0.slug', 'zulu-trashed'));
});

test('platform product show paginates and searches the branch network table', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $product = Product::factory()->create(['slug' => 'network-pack']);
    Branch::factory()->count(20)->create();
    $total = Branch::query()->count();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.products.show', [
            'product' => $product,
            'per_page' => 15,
            'page' => 2,
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/catalog/products/show')
            ->where('table.per_page', 15)
            ->where('table.meta.current_page', 2)
            ->where('table.meta.total', $total)
            ->has('table.data', $total - 15));

    $searched = Branch::query()->orderBy('id')->first();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.products.show', [
            'product' => $product,
            'filter' => ['search' => $searched->name],
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('table.data', fn ($rows) => collect($rows)
                ->contains(fn ($row) => $row['slug'] === $searched->slug)));
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

test('platform generates a prefixed sku when the sku is omitted', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $category = Category::factory()->create();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.products.store'), [
            'category_ids' => [$category->id],
            'name' => 'Auto Sku Kit',
            'slug' => 'auto-sku-kit',
            'status' => true,
        ])
        ->assertRedirect(route('platform.products.show', 'auto-sku-kit'));

    $product = Product::query()->where('slug', 'auto-sku-kit')->firstOrFail();

    expect($product->sku)->toBe(Product::skuFromSlug('auto-sku-kit'))
        ->and($product->sku)->toStartWith((string) config('catalog.sku_prefix'));
});

test('platform product suggestions exclude the current product', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $keep = Product::factory()->create(['name' => 'Exclude Twin Alpha']);
    $self = Product::factory()->create(['name' => 'Exclude Twin Beta']);

    $this->actingAs($admin, 'platform')
        ->getJson(route('platform.products.suggestions', [
            'q' => 'Exclude Twin',
            'exclude' => $self->id,
        ]))
        ->assertOk()
        ->assertJsonFragment(['id' => $keep->id])
        ->assertJsonMissing(['id' => $self->id]);
});
