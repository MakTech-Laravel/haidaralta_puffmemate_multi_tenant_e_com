<?php

use App\Models\Branch;
use App\Services\Order\Allocation\BranchDistanceMatrix;
use App\Services\Order\Allocation\StockTransferPlanner;

test('a line fully covered locally needs no transfer leg', function () {
    $destination = Branch::factory()->create(['latitude' => -33.8688, 'longitude' => 151.2093]);
    $branches = collect([$destination]);

    $matrix = app(BranchDistanceMatrix::class);
    $matrix->build($branches, -33.8688, 151.2093);

    $plan = app(StockTransferPlanner::class)->plan(
        $destination,
        [['product_id' => 1, 'variant_id' => null, 'quantity' => 3]],
        [$destination->id => ['1:0' => 5]],
        $branches,
        $matrix,
    );

    expect($plan)->not->toBeNull()
        ->and($plan['transfers'])->toHaveCount(0)
        ->and($plan['transferred_units'])->toBe(0)
        ->and($plan['lines'][0]['local_quantity'])->toBe(3);
});

test('a shortfall is sourced from the nearest branch that has stock', function () {
    $destination = Branch::factory()->create(['latitude' => -33.8688, 'longitude' => 151.2093]);
    $near = Branch::factory()->create(['latitude' => -33.9, 'longitude' => 151.2]);
    $far = Branch::factory()->create(['latitude' => -37.8136, 'longitude' => 144.9631]);
    $branches = collect([$destination, $near, $far]);

    $matrix = app(BranchDistanceMatrix::class);
    $matrix->build($branches, -33.8688, 151.2093);

    $plan = app(StockTransferPlanner::class)->plan(
        $destination,
        [['product_id' => 1, 'variant_id' => null, 'quantity' => 2]],
        [
            $destination->id => ['1:0' => 0],
            $near->id => ['1:0' => 2],
            $far->id => ['1:0' => 2],
        ],
        $branches,
        $matrix,
    );

    expect($plan)->not->toBeNull()
        ->and($plan['transfers'])->toHaveCount(1)
        ->and($plan['transfers'][0]['source_branch_id'])->toBe($near->id)
        ->and($plan['transferred_units'])->toBe(2);
});

test('a shortfall with no sufficient source anywhere makes the destination infeasible', function () {
    $destination = Branch::factory()->create(['latitude' => -33.8688, 'longitude' => 151.2093]);
    $branches = collect([$destination]);

    $matrix = app(BranchDistanceMatrix::class);
    $matrix->build($branches, -33.8688, 151.2093);

    $plan = app(StockTransferPlanner::class)->plan(
        $destination,
        [['product_id' => 1, 'variant_id' => null, 'quantity' => 5]],
        [$destination->id => ['1:0' => 1]],
        $branches,
        $matrix,
    );

    expect($plan)->toBeNull();
});
