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

test('platform can create a product with primary image and gallery', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $category = Category::factory()->create();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.products.store'), [
            'category_id' => $category->id,
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
            'category_id' => $product->category_id,
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
