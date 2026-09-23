<?php

use App\Enums\RoleEnum;
use App\Enums\UserType;
use App\Models\HomeBanner;
use App\Models\User;
use App\Support\PlatformTeamResolver;
use Database\Seeders\BranchSeeder;
use Database\Seeders\HomeBannerSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);
    Storage::fake('public');
});

test('platform admin can list home banners', function () {
    $this->seed(HomeBannerSeeder::class);
    $admin = User::query()->where('email', UserSeeder::PLATFORM_ADMIN)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.home-banners.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('platform/home-banners/index')
            ->has('banners', 2));
});

test('platform staff without manage-pages cannot open home banners', function () {
    $user = User::factory()->create([
        'user_type' => UserType::PLATFORM,
        'email_verified_at' => now(),
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId(PlatformTeamResolver::PLATFORM_TEAM_ID);
    $user->assignRole(RoleEnum::STAFF->value);

    $this->actingAs($user, 'platform')
        ->get(route('platform.home-banners.index'))
        ->assertForbidden();
});

test('guests are redirected from home banners', function () {
    $this->get(route('platform.home-banners.index'))
        ->assertRedirect(route('login'));
});

test('platform admin can create a home banner', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_ADMIN)->firstOrFail();
    $image = UploadedFile::fake()->image('hero.jpg', 1600, 900);

    $this->actingAs($admin, 'platform')
        ->post(route('platform.home-banners.store'), [
            'is_active' => true,
            'eyebrow' => 'TEST LABEL',
            'title' => 'Fresh banner',
            'description' => 'A short description for the slide.',
            'primary_cta_label' => 'Shop',
            'primary_cta_href' => '/shop',
            'secondary_cta_label' => 'Deals',
            'secondary_cta_href' => '#home-deals',
            'image' => $image,
        ])
        ->assertRedirect(route('platform.home-banners.index'));

    $banner = HomeBanner::query()->first();

    expect($banner)->not->toBeNull()
        ->and($banner->title)->toBe('Fresh banner')
        ->and($banner->is_active)->toBeTrue()
        ->and($banner->image_path)->not->toBeNull();

    Storage::disk('public')->assertExists($banner->image_path);
});

test('platform admin can update reorder toggle and delete a banner', function () {
    $this->seed(HomeBannerSeeder::class);
    $admin = User::query()->where('email', UserSeeder::PLATFORM_ADMIN)->firstOrFail();

    $first = HomeBanner::query()->orderBy('sort_order')->firstOrFail();
    $second = HomeBanner::query()->orderBy('sort_order')->skip(1)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->put(route('platform.home-banners.update', $first), [
            'is_active' => true,
            'eyebrow' => 'UPDATED',
            'title' => 'Updated title',
            'description' => 'Updated description text.',
            'primary_cta_label' => 'Shop',
            'primary_cta_href' => '/shop',
            'secondary_cta_label' => 'Deals',
            'secondary_cta_href' => '#home-deals',
        ])
        ->assertRedirect(route('platform.home-banners.index'));

    expect($first->fresh()->title)->toBe('Updated title')
        ->and($first->fresh()->eyebrow)->toBe('UPDATED');

    $this->actingAs($admin, 'platform')
        ->post(route('platform.home-banners.move', $second), [
            'direction' => 'up',
        ])
        ->assertRedirect(route('platform.home-banners.index'));

    $ordered = HomeBanner::query()->orderBy('sort_order')->orderBy('id')->pluck('id')->all();
    expect($ordered[0])->toBe($second->id);

    $this->actingAs($admin, 'platform')
        ->patch(route('platform.home-banners.toggle', $second), [
            'is_active' => false,
        ])
        ->assertRedirect(route('platform.home-banners.index'));

    expect($second->fresh()->is_active)->toBeFalse();

    $this->actingAs($admin, 'platform')
        ->delete(route('platform.home-banners.destroy', $second))
        ->assertRedirect(route('platform.home-banners.index'));

    expect(HomeBanner::query()->find($second->id))->toBeNull();
});

test('storefront home only receives active banners in order', function () {
    $this->seed(HomeBannerSeeder::class);

    $active = HomeBanner::query()->orderBy('sort_order')->firstOrFail();
    $hidden = HomeBanner::query()->orderBy('sort_order')->skip(1)->firstOrFail();
    $hidden->update(['is_active' => false]);

    HomeBanner::query()->create([
        'sort_order' => 0,
        'is_active' => true,
        'eyebrow' => 'FIRST',
        'title' => 'First slide',
        'description' => 'Should appear first.',
        'image_path' => '/images/storefront/hero-1.jpg',
        'primary_cta_label' => 'Shop',
        'primary_cta_href' => '/shop',
        'secondary_cta_label' => 'Deals',
        'secondary_cta_href' => '#home-deals',
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/home')
            ->has('heroSlides', 2)
            ->where('heroSlides.0.title', 'First slide')
            ->where('heroSlides.1.id', (string) $active->id)
            ->where('heroSlides.1.image', '/images/storefront/hero-1.jpg'));
});
