<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\Media\MediaUploadService;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

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

    $primary = $product->getFirstMedia('image');

    expect($primary)->not->toBeNull()
        ->and($primary->file_name)->toEndWith('.svg')
        ->and(Storage::disk('public')->exists($primary->getPathRelativeToRoot()))->toBeTrue()
        ->and($product->getMedia('gallery'))->toHaveCount(2);
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

    $primary = $product->getFirstMedia('image');
    $gallery = $product->getMedia('gallery');

    expect($primary)->not->toBeNull()
        ->and($primary->mime_type)->toBe('image/webp')
        ->and(Storage::disk('public')->exists($primary->getPathRelativeToRoot()))->toBeTrue()
        ->and($gallery)->toHaveCount(2)
        ->and($gallery->every(fn ($media): bool => $media->mime_type === 'image/webp'))->toBeTrue();

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
    $product = Product::factory()->create(['slug' => 'gallery-edit']);

    [$keep, $remove] = app(MediaUploadService::class)->syncMany($product, 'gallery', [
        UploadedFile::fake()->image('one.jpg', 200, 200),
        UploadedFile::fake()->image('two.jpg', 200, 200),
    ]);
    $removedPath = $remove->getPathRelativeToRoot();

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

    $remaining = $product->fresh()->getMedia('gallery');

    expect($remaining->pluck('id')->all())->toBe([$keep->id])
        ->and(Media::query()->whereKey($remove->id)->exists())->toBeFalse()
        ->and(Storage::disk('public')->exists($removedPath))->toBeFalse();
});

test('platform cannot remove gallery media that belongs to another product', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $product = Product::factory()->create(['slug' => 'gallery-owner']);
    $other = Product::factory()->create(['slug' => 'gallery-other']);

    [$foreign] = app(MediaUploadService::class)->syncMany($other, 'gallery', [
        UploadedFile::fake()->image('foreign.jpg', 200, 200),
    ]);

    $this->actingAs($admin, 'platform')
        ->put(route('platform.products.update', $product), [
            'category_ids' => $product->categories()->pluck('categories.id')->all(),
            'name' => $product->name,
            'slug' => $product->slug,
            'sku' => $product->sku,
            'status' => true,
            'remove_gallery_ids' => [$foreign->id],
        ])
        ->assertSessionHasErrors('remove_gallery_ids.0');

    expect(Media::query()->whereKey($foreign->id)->exists())->toBeTrue();
});

test('platform can replace and remove the primary product image', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $product = Product::factory()->create(['slug' => 'primary-edit']);
    app(MediaUploadService::class)->syncSingle($product, 'image', UploadedFile::fake()->image('old.jpg', 200, 200));

    $payload = [
        'category_ids' => $product->categories()->pluck('categories.id')->all(),
        'name' => $product->name,
        'slug' => $product->slug,
        'sku' => $product->sku,
        'status' => true,
    ];

    $this->actingAs($admin, 'platform')
        ->put(route('platform.products.update', $product), [
            ...$payload,
            'image' => UploadedFile::fake()->image('new.jpg', 200, 200),
        ])
        ->assertRedirect();

    $media = $product->fresh()->getMedia('image');

    expect($media)->toHaveCount(1)
        ->and($media->first()->file_name)->toBe('new.webp');

    $this->actingAs($admin, 'platform')
        ->put(route('platform.products.update', $product), [...$payload, 'remove_image' => true])
        ->assertRedirect();

    expect($product->fresh()->hasMedia('image'))->toBeFalse();
});
