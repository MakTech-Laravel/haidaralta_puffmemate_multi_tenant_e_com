<?php

use App\Models\Branch;
use App\Models\BranchShippingRule;
use App\Services\Order\ShippingCalculator;

function makeShippingBranch(?BranchShippingRule $rule): Branch
{
    $branch = Branch::factory()->create();
    $branch->setRelation('shippingRule', $rule);

    return $branch;
}

test('distance within the free delivery radius is free', function () {
    $branch = makeShippingBranch(BranchShippingRule::factory()->make([
        'base_fee' => 5,
        'free_delivery_distance_km' => 3,
        'cost_per_km' => 1.5,
        'minimum_delivery_charge' => 5,
    ]));

    expect(app(ShippingCalculator::class)->cost($branch, 2.0))->toBe(0.0);
});

test('beyond the free radius the fee is base plus per km, floored by the minimum charge', function () {
    $branch = makeShippingBranch(BranchShippingRule::factory()->make([
        'base_fee' => 5,
        'free_delivery_distance_km' => 3,
        'cost_per_km' => 2,
        'minimum_delivery_charge' => 5,
    ]));

    // 5 + 2 * 10 = 25, above the 5 minimum.
    expect(app(ShippingCalculator::class)->cost($branch, 10.0))->toBe(25.0);
});

test('the minimum delivery charge is respected when the formula result is lower', function () {
    $branch = makeShippingBranch(BranchShippingRule::factory()->make([
        'base_fee' => 1,
        'free_delivery_distance_km' => 0,
        'cost_per_km' => 0.5,
        'minimum_delivery_charge' => 8,
    ]));

    // 1 + 0.5 * 2 = 2, below the 8 minimum.
    expect(app(ShippingCalculator::class)->cost($branch, 2.0))->toBe(8.0);
});

test('a branch without a configured rule falls back to the app defaults', function () {
    $branch = makeShippingBranch(null);

    config([
        'catalog.default_shipping_base_fee' => 4,
        'catalog.default_shipping_free_distance_km' => 0,
        'catalog.default_shipping_cost_per_km' => 1,
        'catalog.default_shipping_minimum_charge' => 4,
    ]);

    expect(app(ShippingCalculator::class)->cost($branch, 6.0))->toBe(10.0);
});

test('a null distance is treated as zero distance', function () {
    config(['catalog.default_shipping_free_distance_km' => 0]);

    $branch = makeShippingBranch(BranchShippingRule::factory()->make([
        'base_fee' => 5,
        'free_delivery_distance_km' => null,
        'cost_per_km' => 2,
        'minimum_delivery_charge' => 5,
    ]));

    expect(app(ShippingCalculator::class)->cost($branch, null))->toBe(5.0);
});
