<?php

use App\Models\PriceRange;
use App\Models\ShopPriceFilterSetting;
use App\Models\User;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);
});

test('platform can manage shop price ranges', function () {
    $user = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    $this->actingAs($user, 'platform')
        ->post(route('platform.price-ranges.store'), [
            'label' => 'Under $50',
            'slug' => 'under-50',
            'min_amount' => null,
            'max_amount' => 50,
            'status' => true,
        ])
        ->assertRedirect(route('platform.price-ranges.index'));

    $range = PriceRange::query()->where('slug', 'under-50')->firstOrFail();

    expect($range->label)->toBe('Under $50')
        ->and((float) $range->max_amount)->toBe(50.0)
        ->and($range->status)->toBeTrue();

    $this->actingAs($user, 'platform')
        ->get(route('platform.price-ranges.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('platform/catalog/price-ranges/index')
            ->has('priceRanges', 1)
            ->has('sliderBounds.slider_min')
            ->has('sliderBounds.slider_max')
            ->where('priceRanges.0.slug', 'under-50'));

    $this->actingAs($user, 'platform')
        ->patch(route('platform.price-ranges.status', $range), [
            'status' => false,
        ])
        ->assertRedirect(route('platform.price-ranges.index'));

    expect($range->refresh()->status)->toBeFalse();
});

test('platform can update shop price slider bounds', function () {
    $user = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    $this->actingAs($user, 'platform')
        ->put(route('platform.price-ranges.bounds'), [
            'slider_min' => 100,
            'slider_max' => 50000,
        ])
        ->assertRedirect(route('platform.price-ranges.index'));

    $settings = ShopPriceFilterSetting::query()->firstOrFail();

    expect((float) $settings->slider_min)->toBe(100.0)
        ->and((float) $settings->slider_max)->toBe(50000.0);

    $this->actingAs($user, 'platform')
        ->get(route('platform.price-ranges.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('sliderBounds.slider_min', '100.00')
            ->where('sliderBounds.slider_max', '50000.00'));
});

test('shop price slider max must exceed min', function () {
    $user = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    $this->actingAs($user, 'platform')
        ->put(route('platform.price-ranges.bounds'), [
            'slider_min' => 500,
            'slider_max' => 100,
        ])
        ->assertSessionHasErrors('slider_max');
});

test('price range requires at least one bound', function () {
    $user = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    $this->actingAs($user, 'platform')
        ->post(route('platform.price-ranges.store'), [
            'label' => 'Invalid',
            'slug' => 'invalid',
            'min_amount' => null,
            'max_amount' => null,
            'status' => true,
        ])
        ->assertSessionHasErrors('min_amount');
});
