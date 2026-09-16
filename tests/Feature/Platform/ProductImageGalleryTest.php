<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');

    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);
});

test('platform can upload svg primary and gallery images', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $category = Category::factory()->create();

    $svg = UploadedFile::fake()->createWithContent(
        'mark.svg',
        '<?xml version="1.0" encoding="UTF-8"?><svg xmlns="http://www.w3.org/2000/svg" width="32" height="32"></svg>',
    );

    $this->actingAs($admin, 'platform')
        ->post(route('platform.products.store'), [
            'category_ids' => [$category->id],
            'name' => 'SVG Pack',
            'slug' => 'svg-pack',
            'sku' => 'SKU-SVG-1',
            'status' => true,
            'image' => $svg,
            'gallery' => [
                UploadedFile::fake()->createWithContent(
                    'gallery.svg',
                    '<?xml version="1.0" encoding="UTF-8"?><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"></svg>',
                ),
                UploadedFile::fake()->image('gallery.webp', 200, 200),
            ],
        ])
        ->assertRedirect(route('platform.products.show', 'svg-pack'));

    $product = Product::query()->where('slug', 'svg-pack')->firstOrFail();

    expect($product->image)->toEndWith('.svg')
        ->and(Storage::disk('public')->exists($product->image))->toBeTrue()
        ->and(ProductImage::query()->where('product_id', $product->id)->count())->toBe(2);
});

test('platform rejects unsupported primary image mime types', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $category = Category::factory()->create();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.products.store'), [
            'category_ids' => [$category->id],
            'name' => 'Bad Image',
            'slug' => 'bad-image',
            'sku' => 'SKU-BAD-1',
            'status' => true,
            'image' => UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf'),
        ])
        ->assertSessionHasErrors('image');
});

test('platform can create a product with primary image and gallery', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $category = Category::factory()->create();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.products.store'), [
            'category_ids' => [$category->id],
            'name' => 'Gallery Pack',
            'slug' => 'gallery-pack',
            'sku' => 'SKU-GAL-1',
            'status' => true,
            'image' => UploadedFile::fake()->image('primary.jpg', 400, 400),
            'gallery' => [
                UploadedFile::fake()->image('gallery-a.jpg', 400, 400),
                UploadedFile::fake()->image('gallery-b.jpg', 400, 400),
            ],
        ])
        ->assertRedirect(route('platform.products.show', 'gallery-pack'));

    $product = Product::query()->where('slug', 'gallery-pack')->firstOrFail();

    expect($product->image)->not->toBeNull()
        ->and(Storage::disk('public')->exists($product->image))->toBeTrue()
        ->and(ProductImage::query()->where('product_id', $product->id)->count())->toBe(2);

    $this->actingAs($admin, 'platform')
        ->get(route('platform.products.show', $product))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/catalog/products/show')
            ->has('product.gallery', 2)
            ->has('table.data')
            ->has('product.updated_at')
            ->where('product.image_url', fn ($url) => is_string($url) && $url !== ''));
});

test('platform can remove gallery images on update', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $product = Product::factory()->create([
        'slug' => 'gallery-edit',
        'image' => 'products/primary.jpg',
    ]);

    Storage::disk('public')->put('products/primary.jpg', 'primary');
    Storage::disk('public')->put('products/gallery/one.jpg', 'one');
    Storage::disk('public')->put('products/gallery/two.jpg', 'two');

    $keep = ProductImage::factory()->create([
        'product_id' => $product->id,
        'path' => 'products/gallery/one.jpg',
        'sort_order' => 1,
    ]);
    $remove = ProductImage::factory()->create([
        'product_id' => $product->id,
        'path' => 'products/gallery/two.jpg',
        'sort_order' => 2,
    ]);

    $this->actingAs($admin, 'platform')
        ->put(route('platform.products.update', $product), [
            'category_ids' => $product->categories()->pluck('categories.id')->all(),
            'name' => $product->name,
            'slug' => $product->slug,
            'sku' => $product->sku,
            'description' => $product->description,
            'status' => true,
            'remove_gallery_ids' => [$remove->id],
        ])
        ->assertRedirect(route('platform.products.show', $product));

    expect(ProductImage::query()->whereKey($keep->id)->exists())->toBeTrue()
        ->and(ProductImage::query()->whereKey($remove->id)->exists())->toBeFalse()
        ->and(Storage::disk('public')->exists('products/gallery/two.jpg'))->toBeFalse();
});
