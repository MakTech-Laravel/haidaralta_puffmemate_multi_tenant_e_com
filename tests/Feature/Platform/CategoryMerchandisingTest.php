<?php

use App\Models\Branch;
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

test('platform can toggle category status from the list', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $category = Category::factory()->create(['status' => true]);

    $this->actingAs($admin, 'platform')
        ->from(route('platform.categories.index'))
        ->put(route('platform.categories.status', $category))
        ->assertRedirect(route('platform.categories.index'))
        ->assertInertiaFlash('toast', [
            'type' => 'success',
            'message' => 'Category deactivated.',
        ]);

    expect($category->fresh()->status)->toBeFalse();
});

test('platform can toggle category featured from the list', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $category = Category::factory()->create(['featured' => false]);

    $this->actingAs($admin, 'platform')
        ->from(route('platform.categories.index'))
        ->put(route('platform.categories.featured', $category))
        ->assertRedirect(route('platform.categories.index'))
        ->assertInertiaFlash('toast', [
            'type' => 'success',
            'message' => 'Category marked as featured.',
        ]);

    expect($category->fresh()->featured)->toBeTrue();
});

test('platform can toggle category nav from the list', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $category = Category::factory()->create(['show_in_nav' => false]);

    $this->actingAs($admin, 'platform')
        ->from(route('platform.categories.index'))
        ->put(route('platform.categories.nav', $category))
        ->assertRedirect(route('platform.categories.index'))
        ->assertInertiaFlash('toast', [
            'type' => 'success',
            'message' => 'Category is now available on nav.',
        ]);

    expect($category->fresh()->show_in_nav)->toBeTrue();
});

test('platform featured toggle respects the configured maximum', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $max = (int) config('catalog.featured_categories_max');
    Category::factory()->count($max)->featured()->create();
    $category = Category::factory()->create(['featured' => false]);

    $this->actingAs($admin, 'platform')
        ->from(route('platform.categories.index'))
        ->put(route('platform.categories.featured', $category))
        ->assertRedirect(route('platform.categories.index'))
        ->assertInertiaFlash('toast', [
            'type' => 'error',
            'message' => "Only {$max} categories can be featured at a time.",
        ]);

    expect($category->fresh()->featured)->toBeFalse();
});

test('platform nav toggle respects the configured maximum', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $max = (int) config('catalog.nav_categories_max');
    Category::factory()->count($max)->inNav()->create();
    $category = Category::factory()->create(['show_in_nav' => false]);

    $this->actingAs($admin, 'platform')
        ->from(route('platform.categories.index'))
        ->put(route('platform.categories.nav', $category))
        ->assertRedirect(route('platform.categories.index'))
        ->assertInertiaFlash('toast', [
            'type' => 'error',
            'message' => "Only {$max} categories can appear in the header navigation.",
        ]);

    expect($category->fresh()->show_in_nav)->toBeFalse();
});

test('platform can update nav sort when the category is in nav', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $category = Category::factory()->inNav(2)->create();

    $this->actingAs($admin, 'platform')
        ->from(route('platform.categories.index'))
        ->put(route('platform.categories.nav-sort', $category), [
            'nav_sort' => 7,
        ])
        ->assertRedirect(route('platform.categories.index'))
        ->assertInertiaFlash('toast', [
            'type' => 'success',
            'message' => 'Nav position updated.',
        ]);

    expect($category->fresh()->nav_sort)->toBe(7);
});

test('platform cannot update nav sort when the category is not in nav', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $category = Category::factory()->create([
        'show_in_nav' => false,
        'nav_sort' => null,
    ]);

    $this->actingAs($admin, 'platform')
        ->from(route('platform.categories.index'))
        ->put(route('platform.categories.nav-sort', $category), [
            'nav_sort' => 4,
        ])
        ->assertRedirect(route('platform.categories.index'))
        ->assertInertiaFlash('toast', [
            'type' => 'error',
            'message' => 'Set the category as available on nav before choosing a position.',
        ]);

    expect($category->fresh()->nav_sort)->toBeNull();
});

test('branch can toggle category featured from the list', function () {
    config(['catalog.branch_can_view_categories' => true]);

    $staff = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $branch = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $category = Category::factory()->create(['featured' => false]);

    $this->actingAs($staff, 'branch')
        ->from(route('branch.categories.index', $branch))
        ->put(route('branch.categories.featured', [$branch, $category]))
        ->assertRedirect(route('branch.categories.index', $branch))
        ->assertInertiaFlash('toast', [
            'type' => 'success',
            'message' => 'Category marked as featured.',
        ]);

    expect($category->fresh()->featured)->toBeTrue();
});

test('platform shares catalog kill-switches on the category index', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.categories.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('catalog.branch_can_create_categories', false)
            ->where('catalog.branch_can_view_categories', false)
            ->where('catalog.show_parent_category', false)
            ->where('catalog.show_product_variants', false));
});
