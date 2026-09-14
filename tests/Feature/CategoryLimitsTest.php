<?php

use App\Models\Category;
use App\Models\User;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);
});

test('platform cannot feature more categories than the configured maximum', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $max = (int) config('catalog.featured_categories_max');

    Category::factory()->count($max)->featured()->create();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.categories.store'), [
            'name' => 'Overflow Featured',
            'slug' => 'overflow-featured',
            'featured' => true,
            'status' => true,
        ])
        ->assertSessionHasErrors('featured');
});

test('platform cannot put more categories in nav than the configured maximum', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $max = (int) config('catalog.nav_categories_max');

    Category::factory()->count($max)->inNav()->create();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.categories.store'), [
            'name' => 'Overflow Nav',
            'slug' => 'overflow-nav',
            'show_in_nav' => true,
            'status' => true,
        ])
        ->assertSessionHasErrors('show_in_nav');
});

test('platform can store slogan and nav fields within limits', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.categories.store'), [
            'name' => 'Slogan Category',
            'slug' => 'slogan-category',
            'slogan' => 'Premium tobacco, best brands',
            'featured' => true,
            'show_in_nav' => true,
            'nav_sort' => 3,
            'status' => true,
        ])
        ->assertRedirect();

    $category = Category::query()->where('slug', 'slogan-category')->firstOrFail();

    expect($category->slogan)->toBe('Premium tobacco, best brands')
        ->and($category->featured)->toBeTrue()
        ->and($category->show_in_nav)->toBeTrue()
        ->and($category->nav_sort)->toBe(3);
});

test('updating an already featured category does not hit the featured limit', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $max = (int) config('catalog.featured_categories_max');

    $others = Category::factory()->count($max - 1)->featured()->create();
    $category = Category::factory()->featured()->create([
        'name' => 'Keep Featured',
        'slug' => 'keep-featured',
        'slogan' => 'Original slogan',
    ]);

    expect(Category::query()->where('featured', true)->count())->toBe($max);

    $this->actingAs($admin, 'platform')
        ->put(route('platform.categories.update', $category), [
            'name' => 'Keep Featured',
            'slug' => 'keep-featured',
            'slogan' => 'Updated slogan',
            'featured' => true,
            'show_in_nav' => false,
            'status' => true,
        ])
        ->assertRedirect(route('platform.categories.show', $category->fresh()));

    expect($category->fresh()->slogan)->toBe('Updated slogan')
        ->and($others)->not->toBeEmpty();
});
