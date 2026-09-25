<?php

use App\Enums\CouponType;
use App\Models\Coupon;
use App\Models\DeliveryMethod;
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

test('platform can manage coupons', function () {
    $user = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    $this->actingAs($user, 'platform')
        ->post(route('platform.coupons.store'), [
            'code' => 'WELCOME10',
            'type' => CouponType::Fixed->value,
            'value' => 10,
            'min_subtotal' => 50,
            'max_discount' => null,
            'starts_at' => now()->toDateString(),
            'ends_at' => now()->addMonth()->toDateString(),
            'usage_limit' => null,
            'usage_limit_per_customer' => 1,
            'status' => true,
        ])
        ->assertRedirect(route('platform.coupons.index'));

    $coupon = Coupon::query()->where('code', 'WELCOME10')->firstOrFail();

    expect($coupon->type)->toBe(CouponType::Fixed)
        ->and((float) $coupon->value)->toBe(10.0);

    $this->actingAs($user, 'platform')
        ->get(route('platform.coupons.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('platform/coupons/index')
            ->has('coupons', 1)
            ->where('coupons.0.code', 'WELCOME10'));

    $this->actingAs($user, 'platform')
        ->put(route('platform.coupons.update', $coupon), [
            'code' => 'WELCOME10',
            'type' => CouponType::Percent->value,
            'value' => 15,
            'min_subtotal' => 80,
            'max_discount' => 40,
            'starts_at' => now()->toDateString(),
            'ends_at' => now()->addMonth()->toDateString(),
            'usage_limit' => 100,
            'usage_limit_per_customer' => 2,
            'status' => true,
        ])
        ->assertRedirect(route('platform.coupons.index'));

    expect($coupon->fresh()->type)->toBe(CouponType::Percent);
});

test('platform can update delivery settings and open payment methods', function () {
    $user = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    $this->actingAs($user, 'platform')
        ->get(route('platform.delivery-settings.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('platform/delivery-settings/index')
            ->has('methods'));

    $method = DeliveryMethod::query()->firstOrFail();

    $this->actingAs($user, 'platform')
        ->put(route('platform.delivery-settings.update', $method), [
            'label' => 'Same-day local delivery',
            'description' => 'Updated copy',
            'fee' => 4.5,
            'requires_slot' => true,
            'status' => true,
        ])
        ->assertRedirect(route('platform.delivery-settings.index'));

    $this->actingAs($user, 'platform')
        ->get(route('platform.payment-methods.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('platform/settings/payment-gateway')
            ->has('methods'));
});
