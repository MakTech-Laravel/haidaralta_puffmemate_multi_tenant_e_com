<?php

use App\Models\Branch;
use App\Services\Order\Allocation\BranchDistanceMatrix;

test('the matrix precomputes customer and branch-pair distances once per build', function () {
    $sydney = Branch::factory()->create(['latitude' => -33.8688, 'longitude' => 151.2093]);
    $melbourne = Branch::factory()->create(['latitude' => -37.8136, 'longitude' => 144.9631]);

    $matrix = app(BranchDistanceMatrix::class);
    $matrix->build(collect([$sydney, $melbourne]), -33.8688, 151.2093);

    expect($matrix->customerDistanceTo($sydney->id))->toBe(0.0)
        ->and($matrix->customerDistanceTo($melbourne->id))->toBeGreaterThan(700.0)
        ->and($matrix->between($sydney->id, $melbourne->id))->toBeGreaterThan(700.0)
        ->and($matrix->between($sydney->id, $melbourne->id))->toBe($matrix->between($melbourne->id, $sydney->id));
});

test('branches missing coordinates resolve to a null distance', function () {
    $noCoords = Branch::factory()->create(['latitude' => null, 'longitude' => null]);
    $sydney = Branch::factory()->create(['latitude' => -33.8688, 'longitude' => 151.2093]);

    $matrix = app(BranchDistanceMatrix::class);
    $matrix->build(collect([$noCoords, $sydney]), -33.8688, 151.2093);

    expect($matrix->customerDistanceTo($noCoords->id))->toBeNull()
        ->and($matrix->between($noCoords->id, $sydney->id))->toBeNull();
});

test('sortByDistanceFrom orders other branches nearest first using the precomputed matrix', function () {
    $destination = Branch::factory()->create(['latitude' => -33.8688, 'longitude' => 151.2093]);
    $near = Branch::factory()->create(['latitude' => -33.9, 'longitude' => 151.2]);
    $far = Branch::factory()->create(['latitude' => -37.8136, 'longitude' => 144.9631]);

    $matrix = app(BranchDistanceMatrix::class);
    $branches = collect([$destination, $near, $far]);
    $matrix->build($branches, -33.8688, 151.2093);

    $sorted = $matrix->sortByDistanceFrom($destination->id, $branches);

    expect($sorted->pluck('id')->all())->toBe([$near->id, $far->id]);
});
