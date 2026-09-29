<?php

use App\Models\Branch;
use App\Services\Order\Allocation\BranchCandidateFinder;

test('active branches are loaded ordered by id with the shipping rule eager loaded', function () {
    $second = Branch::factory()->create(['status' => true]);
    $first = Branch::factory()->create(['status' => true]);
    Branch::factory()->create(['status' => false]);

    $branches = app(BranchCandidateFinder::class)->activeBranches();

    expect($branches->pluck('id')->all())->toBe(
        collect([$second, $first])->pluck('id')->sort()->values()->all()
    )->and($branches->first()->relationLoaded('shippingRule'))->toBeTrue();
});

test('a null delivery radius can deliver to any distance', function () {
    $branch = Branch::factory()->make(['delivery_radius_km' => null]);

    expect(app(BranchCandidateFinder::class)->canDeliverToPin($branch, 99999.0))->toBeTrue();
});

test('a set delivery radius rejects a pin outside it', function () {
    $branch = Branch::factory()->make(['delivery_radius_km' => 10]);

    expect(app(BranchCandidateFinder::class)->canDeliverToPin($branch, 10.0))->toBeTrue()
        ->and(app(BranchCandidateFinder::class)->canDeliverToPin($branch, 10.01))->toBeFalse()
        ->and(app(BranchCandidateFinder::class)->canDeliverToPin($branch, null))->toBeFalse();
});

test('a branch with no workload cap is always under cap', function () {
    $branch = Branch::factory()->make(['max_active_fulfillments' => null]);

    expect(app(BranchCandidateFinder::class)->isUnderWorkloadCap($branch, 1000))->toBeTrue();
});

test('a branch at or over its workload cap is rejected', function () {
    $branch = Branch::factory()->make(['max_active_fulfillments' => 5]);
    $finder = app(BranchCandidateFinder::class);

    expect($finder->isUnderWorkloadCap($branch, 4))->toBeTrue()
        ->and($finder->isUnderWorkloadCap($branch, 5))->toBeFalse();
});
