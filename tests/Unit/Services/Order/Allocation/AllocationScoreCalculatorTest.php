<?php

use App\Services\Order\Allocation\AllocationScoreCalculator;

function scoredCandidate(array $overrides = []): array
{
    return array_merge([
        'needs_transfer' => 0,
        'operational_cost' => 0.0,
        'distance_km' => 0.0,
        'shipping_cost' => 0.0,
        'load' => 0,
        'transferred_units' => 0,
        'priority' => 100,
        'branch_id' => 1,
    ], $overrides);
}

test('needs_transfer is an absolute veto over every other dimension', function () {
    $calculator = app(AllocationScoreCalculator::class);

    $needsTransferButCheap = scoredCandidate(['needs_transfer' => 1, 'operational_cost' => 0, 'distance_km' => 0, 'branch_id' => 1]);
    $noTransferButExpensive = scoredCandidate(['needs_transfer' => 0, 'operational_cost' => 1000, 'distance_km' => 1000, 'branch_id' => 2]);

    expect($calculator->compare($needsTransferButCheap, $noTransferButExpensive))->toBeGreaterThan(0);
});

test('branch id is the final deterministic tie-break when every other dimension matches', function () {
    $calculator = app(AllocationScoreCalculator::class);

    $lower = scoredCandidate(['branch_id' => 5]);
    $higher = scoredCandidate(['branch_id' => 9]);

    expect($calculator->compare($lower, $higher))->toBeLessThan(0)
        ->and($calculator->compare($higher, $lower))->toBeGreaterThan(0);
});

test('workload is compared before transferred units and priority', function () {
    $calculator = app(AllocationScoreCalculator::class);

    $lighterLoad = scoredCandidate(['load' => 1, 'transferred_units' => 10, 'priority' => 100, 'branch_id' => 1]);
    $heavierLoad = scoredCandidate(['load' => 2, 'transferred_units' => 0, 'priority' => 1, 'branch_id' => 2]);

    expect($calculator->compare($lighterLoad, $heavierLoad))->toBeLessThan(0);
});
