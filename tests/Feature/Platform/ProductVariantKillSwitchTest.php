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

test('platform variant store is not found while variants are hidden', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $product = Product::factory()->create();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.products.variants.store', $product), [
            'name' => 'Gold',
            'status' => true,
        ])
        ->assertNotFound();
});

test('platform variant store succeeds when variants are enabled', function () {
    config(['catalog.show_product_variants' => true]);

    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $product = Product::factory()->create();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.products.variants.store', $product), [
            'name' => 'Gold',
            'status' => true,
        ])
        ->assertRedirect(route('platform.products.show', $product));

    expect($product->variants()->where('name', 'Gold')->exists())->toBeTrue();
});
