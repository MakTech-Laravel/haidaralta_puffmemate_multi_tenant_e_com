<?php

use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use Database\Seeders\BranchSeeder;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::fake([
        'images.unsplash.com/*' => Http::response('fake-image-bytes', 200, [
            'Content-Type' => 'image/jpeg',
        ]),
    ]);

    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);
    $this->seed(CatalogSeeder::class);
});

test('catalog seeder stores public web image paths for categories and products', function () {
    $category = Category::query()->where('slug', 'cigarettes')->firstOrFail();
    $storefrontProduct = Product::query()->where('slug', 'marlboro-gold-soft-pack-20s')->firstOrFail();
    $remoteProduct = Product::query()->where('slug', 'horizon-red-soft-pack-25s')->firstOrFail();

    expect($category->image)->toStartWith('/images/')
        ->and(is_file(public_path(ltrim((string) $category->image, '/'))))->toBeTrue()
        ->and($storefrontProduct->image)->toBe('/images/storefront/product-marlboro-gold.jpg')
        ->and(is_file(public_path(ltrim((string) $storefrontProduct->image, '/'))))->toBeTrue()
        ->and($remoteProduct->image)->toStartWith('/images/catalog/products/')
        ->and(is_file(public_path(ltrim((string) $remoteProduct->image, '/'))))->toBeTrue();
});

test('branch seeder stores the public logo path', function () {
    $branch = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();

    expect($branch->logo)->toBe('/images/storefront/logo.png');
});
