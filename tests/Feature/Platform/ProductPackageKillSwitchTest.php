<?php

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

test('platform package store is not found while packages are hidden', function () {
    config(['catalog.show_product_packages' => false]);

    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $product = Product::factory()->create();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.products.packages.store', $product), [
            'label' => 'Pack of 2',
            'quantity' => 2,
            'price' => 160,
            'status' => true,
        ])
        ->assertNotFound();
});

test('platform package store succeeds when packages are enabled', function () {
    config(['catalog.show_product_packages' => true]);

    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $product = Product::factory()->create();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.products.packages.store', $product), [
            'label' => 'Pack of 2',
            'quantity' => 2,
            'price' => 160,
            'status' => true,
        ])
        ->assertRedirect();

    expect($product->packages()->where('label', 'Pack of 2')->exists())->toBeTrue();
});
