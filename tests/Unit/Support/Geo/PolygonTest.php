<?php

use App\Support\Geo\Polygon;

test('normalize closes an open ring and rounds coordinates', function () {
    $normalized = Polygon::normalize([
        ['lat' => -33.88, 'lng' => 151.21],
        ['lat' => -33.88, 'lng' => 151.22],
        ['lat' => -33.87, 'lng' => 151.22],
    ]);

    expect($normalized)->toHaveCount(4)
        ->and($normalized[0])->toBe($normalized[3])
        ->and(Polygon::uniqueVertexCount($normalized))->toBe(3);
});

test('centroid averages unique vertices', function () {
    $ring = Polygon::squareAround(-33.88, 151.21, 0.01);
    $centroid = Polygon::centroid($ring);

    expect($centroid)->not->toBeNull()
        ->and($centroid['lat'])->toBe(-33.88)
        ->and($centroid['lng'])->toBe(151.21);
});

test('contains reports points inside and outside a square ring', function () {
    $ring = Polygon::squareAround(-33.88, 151.21, 0.02);

    expect(Polygon::contains(-33.88, 151.21, $ring))->toBeTrue()
        ->and(Polygon::contains(-34.5, 151.21, $ring))->toBeFalse()
        ->and(Polygon::contains(-33.88, 152.5, $ring))->toBeFalse();
});

test('square around builds a closed four-vertex ring', function () {
    $ring = Polygon::squareAround(-33.8845, 151.2140);

    expect(Polygon::uniqueVertexCount($ring))->toBe(4)
        ->and(count($ring))->toBe(5)
        ->and($ring[0])->toBe($ring[4]);
});
