<?php

use App\Models\Branch;
use App\Models\BranchShippingRule;
use App\Models\User;
use App\Support\PlatformTeamResolver;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);

    app(PermissionRegistrar::class)->setPermissionsTeamId(PlatformTeamResolver::PLATFORM_TEAM_ID);
});

test('delivery settings index lists a shipping rule for every branch', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.delivery-settings.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('platform/delivery-settings/index')
            ->has('methods')
            ->has('branchShippingRules', Branch::query()->count()));
});

test('platform admin can update a branchs shipping rule', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();

    $rule = BranchShippingRule::query()->create([
        'branch_id' => $sydney->id,
        'base_fee' => 5,
        'free_delivery_distance_km' => null,
        'cost_per_km' => 1,
        'minimum_delivery_charge' => 5,
        'status' => true,
    ]);

    $this->actingAs($admin, 'platform')
        ->put(route('platform.delivery-settings.shipping-rules.update', $rule), [
            'base_fee' => 8,
            'free_delivery_distance_km' => 3,
            'cost_per_km' => 2,
            'minimum_delivery_charge' => 10,
            'status' => true,
        ])
        ->assertRedirect(route('platform.delivery-settings.index'));

    $rule->refresh();

    expect((float) $rule->base_fee)->toBe(8.0)
        ->and((float) $rule->free_delivery_distance_km)->toBe(3.0)
        ->and((float) $rule->cost_per_km)->toBe(2.0)
        ->and((float) $rule->minimum_delivery_charge)->toBe(10.0);
});
