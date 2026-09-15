<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Support\CatalogBackgroundColor;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);
});

test('catalog background color normalizes modes and strips unused keys', function () {
    expect(CatalogBackgroundColor::normalize([
        'mode' => 'solid',
        'color' => '#abc123',
        'from' => '#FFFFFF',
    ]))->toBe([
        'mode' => 'solid',
        'color' => '#ABC123',
    ]);

    expect(CatalogBackgroundColor::normalize([
        'mode' => 'gradient',
        'from' => '#e8f0fe',
        'to' => '#ffffff',
        'angle' => 90,
        'color' => '#111111',
    ]))->toBe([
        'mode' => 'gradient',
        'from' => '#E8F0FE',
        'to' => '#FFFFFF',
        'angle' => 90,
    ]);

    expect(CatalogBackgroundColor::cssBackground([
        'mode' => 'gradient',
        'from' => '#E8F0FE',
        'to' => '#FFFFFF',
        'angle' => 135,
    ]))->toBe('linear-gradient(135deg, #E8F0FE, #FFFFFF)');

    expect(CatalogBackgroundColor::cssBackground(['mode' => 'transparent']))->toBeNull();
});

test('platform can create category and product with solid and gradient color_code', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.categories.store'), [
            'name' => 'Tinted Devices',
            'slug' => 'tinted-devices',
            'status' => true,
            'color_code' => [
                'mode' => 'solid',
                'color' => '#DBEAFE',
            ],
        ])
        ->assertRedirect(route('platform.categories.show', 'tinted-devices'));

    $category = Category::query()->where('slug', 'tinted-devices')->firstOrFail();

    expect($category->color_code)->toBe([
        'mode' => 'solid',
        'color' => '#DBEAFE',
    ]);

    $this->actingAs($admin, 'platform')
        ->post(route('platform.products.store'), [
            'category_ids' => [$category->id],
            'name' => 'Tinted Kit',
            'slug' => 'tinted-kit',
            'sku' => 'SKU-TINT-1',
            'status' => true,
            'color_code' => json_encode([
                'mode' => 'gradient',
                'from' => '#F3E8FF',
                'to' => '#FFFFFF',
                'angle' => 135,
            ]),
        ])
        ->assertRedirect(route('platform.products.show', 'tinted-kit'));

    $product = Product::query()->where('slug', 'tinted-kit')->firstOrFail();

    expect($product->color_code)->toBe([
        'mode' => 'gradient',
        'from' => '#F3E8FF',
        'to' => '#FFFFFF',
        'angle' => 135,
    ]);
});

test('platform can create a product with the catalog default solid red background', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $category = Category::factory()->create();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.products.store'), [
            'category_ids' => [$category->id],
            'name' => 'Default Red Kit',
            'slug' => 'default-red-kit',
            'sku' => 'SKU-RED-1',
            'status' => true,
            'color_code' => json_encode([
                'mode' => 'solid',
                'color' => '#EF4444',
            ]),
        ])
        ->assertRedirect(route('platform.products.show', 'default-red-kit'));

    $product = Product::query()->where('slug', 'default-red-kit')->firstOrFail();

    expect($product->color_code)->toBe([
        'mode' => 'solid',
        'color' => '#EF4444',
    ]);
});

test('platform can create a category with the catalog default solid red background', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.categories.store'), [
            'name' => 'Default Red Category',
            'slug' => 'default-red-category',
            'status' => true,
            'color_code' => [
                'mode' => 'solid',
                'color' => '#EF4444',
            ],
        ])
        ->assertRedirect(route('platform.categories.show', 'default-red-category'));

    $category = Category::query()->where('slug', 'default-red-category')->firstOrFail();

    expect($category->color_code)->toBe([
        'mode' => 'solid',
        'color' => '#EF4444',
    ]);
});

test('solid color_code requires a hex color', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.categories.store'), [
            'name' => 'Broken Tint',
            'slug' => 'broken-tint',
            'status' => true,
            'color_code' => [
                'mode' => 'solid',
            ],
        ])
        ->assertSessionHasErrors('color_code.color');
});

test('gradient color_code requires from and to hex colors', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $category = Category::factory()->create();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.products.store'), [
            'category_ids' => [$category->id],
            'name' => 'Broken Gradient',
            'slug' => 'broken-gradient',
            'status' => true,
            'color_code' => [
                'mode' => 'gradient',
                'from' => '#FFFFFF',
                'angle' => 90,
            ],
        ])
        ->assertSessionHasErrors('color_code.to');
});

test('platform can update product color_code to transparent', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $product = Product::factory()->create([
        'slug' => 'color-update',
        'color_code' => ['mode' => 'solid', 'color' => '#FEE2E2'],
    ]);
    $category = Category::factory()->create();
    $product->categories()->sync([$category->id]);

    $this->actingAs($admin, 'platform')
        ->put(route('platform.products.update', $product), [
            'category_ids' => [$category->id],
            'name' => $product->name,
            'slug' => $product->slug,
            'sku' => $product->sku,
            'description' => $product->description,
            'status' => true,
            'color_code' => ['mode' => 'transparent'],
        ])
        ->assertRedirect(route('platform.products.show', $product));

    expect($product->fresh()->color_code)->toBe(['mode' => 'transparent']);
});
