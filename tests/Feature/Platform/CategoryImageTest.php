<?php

use App\Models\Category;
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

test('platform can upload svg category image', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    $svg = UploadedFile::fake()->createWithContent(
        'category.svg',
        '<?xml version="1.0" encoding="UTF-8"?><svg xmlns="http://www.w3.org/2000/svg" width="32" height="32"></svg>',
    );

    $this->actingAs($admin, 'platform')
        ->post(route('platform.categories.store'), [
            'name' => 'SVG Category',
            'slug' => 'svg-category',
            'status' => true,
            'image' => $svg,
        ])
        ->assertRedirect(route('platform.categories.show', 'svg-category'));

    $category = Category::query()->where('slug', 'svg-category')->firstOrFail();

    expect($category->image)->toEndWith('.svg')
        ->and(Storage::disk('public')->exists($category->image))->toBeTrue();
});

test('platform rejects unsupported category image mime types', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.categories.store'), [
            'name' => 'Bad Category Image',
            'slug' => 'bad-category-image',
            'status' => true,
            'image' => UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf'),
        ])
        ->assertSessionHasErrors('image');
});

test('platform category show includes media and hierarchy counts', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $parent = Category::factory()->create(['name' => 'Parent Show', 'slug' => 'parent-show']);
    $category = Category::factory()->create([
        'name' => 'Child Show',
        'slug' => 'child-show',
        'parent_id' => $parent->id,
    ]);
    Category::factory()->create([
        'name' => 'Grandchild Show',
        'slug' => 'grandchild-show',
        'parent_id' => $category->id,
    ]);

    $this->actingAs($admin, 'platform')
        ->get(route('platform.categories.show', $category))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/catalog/categories/show')
            ->where('category.parent_name', 'Parent Show')
            ->where('category.children_count', 1)
            ->where('category.products_count', 0)
            ->has('category.color_code'));
});
