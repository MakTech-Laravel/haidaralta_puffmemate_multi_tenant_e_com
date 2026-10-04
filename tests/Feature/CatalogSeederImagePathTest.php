<?php

use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use Database\Seeders\BranchSeeder;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

beforeEach(function () {
    Storage::fake('public');

    Http::fake([
        'images.unsplash.com/*' => Http::response(
            file_get_contents(public_path('images/storefront/logo.png')),
            200,
            ['Content-Type' => 'image/png'],
        ),
    ]);

    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);
    $this->seed(CatalogSeeder::class);
});

test('catalog seeder attaches committed public images as media without moving the originals', function () {
    $category = Category::query()->where('slug', 'cigarettes')->firstOrFail();
    $storefrontProduct = Product::query()->where('slug', 'marlboro-gold-soft-pack-20s')->firstOrFail();
    $remoteProduct = Product::query()->where('slug', 'horizon-red-soft-pack-25s')->firstOrFail();

    expect($category->hasMedia('image'))->toBeTrue()
        ->and($category->getFirstMedia('image')->file_name)->toBe('category-cigarettes.png')
        ->and($category->imageUrl())->toBe($category->getFirstMediaUrl('image'))
        ->and($storefrontProduct->getFirstMedia('image')->file_name)->toBe('product-marlboro-gold.jpg')
        ->and(is_file(public_path('images/storefront/product-marlboro-gold.jpg')))->toBeTrue()
        ->and($remoteProduct->getFirstMedia('image')->file_name)->toStartWith('horizon-red-soft-pack-25s')
        ->and(Media::query()->where('collection_name', 'gallery')->exists())->toBeTrue();
});

test('catalog seeder does not duplicate media when re-run', function () {
    $before = Media::query()->count();

    $this->seed(CatalogSeeder::class);

    expect(Media::query()->count())->toBe($before);
});

test('branch seeder stores the public logo path', function () {
    $branch = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();

    expect($branch->hasMedia('logo'))->toBeTrue()
        ->and($branch->getFirstMedia('logo')->getCustomProperty('seed_source'))->toBe('/images/storefront/logo.png')
        ->and($branch->logoUrl())->not->toBeNull();
});
