<?php

use App\Models\Branch;
use App\Models\BranchArea;
use App\Services\Order\Allocation\AreaBranchResolver;
use App\Support\Geo\Polygon;

test('nearest returns the closest active branch whose area polygon contains the pin', function () {
    $near = Branch::factory()->create([
        'latitude' => -33.8688,
        'longitude' => 151.2093,
        'status' => true,
    ]);
    $far = Branch::factory()->create([
        'latitude' => -33.9,
        'longitude' => 151.25,
        'status' => true,
    ]);

    BranchArea::factory()->forBranch($near)->create([
        'latitude' => -33.8688,
        'longitude' => 151.2093,
        'polygon' => Polygon::squareAround(-33.8688, 151.2093, 0.05),
        'is_active' => true,
    ]);
    BranchArea::factory()->forBranch($far)->create([
        'latitude' => -33.8688,
        'longitude' => 151.2093,
        'polygon' => Polygon::squareAround(-33.8688, 151.2093, 0.05),
        'is_active' => true,
    ]);

    $match = app(AreaBranchResolver::class)->nearest(-33.8688, 151.2093);

    expect($match)->not->toBeNull()
        ->and($match['branch']->id)->toBe($near->id)
        ->and($match['distance_km'])->toBeLessThan(1);
});

test('nearest ignores inactive areas and inactive branches', function () {
    $active = Branch::factory()->create([
        'latitude' => -33.8688,
        'longitude' => 151.2093,
        'status' => true,
    ]);
    $inactiveBranch = Branch::factory()->create([
        'latitude' => -33.8688,
        'longitude' => 151.2093,
        'status' => false,
    ]);

    BranchArea::factory()->forBranch($inactiveBranch)->create([
        'polygon' => Polygon::squareAround(-33.8688, 151.2093, 0.05),
        'is_active' => true,
    ]);
    BranchArea::factory()->forBranch($active)->inactive()->create([
        'polygon' => Polygon::squareAround(-33.8688, 151.2093, 0.05),
    ]);

    expect(app(AreaBranchResolver::class)->nearest(-33.8688, 151.2093))->toBeNull();
});

test('nearest returns null when the pin is outside every active area', function () {
    $branch = Branch::factory()->create([
        'latitude' => -33.8688,
        'longitude' => 151.2093,
        'status' => true,
    ]);
    BranchArea::factory()->forBranch($branch)->create([
        'polygon' => Polygon::squareAround(-33.8688, 151.2093, 0.01),
        'is_active' => true,
    ]);

    expect(app(AreaBranchResolver::class)->nearest(-34.5, 150.0))->toBeNull();
});

test('resolveForCheckout falls back to nearest active branch outside every area', function () {
    // Tiny Sydney-only area so Adelaide pin misses the polygon.
    $sydney = Branch::factory()->create([
        'latitude' => -33.8688,
        'longitude' => 151.2093,
        'status' => true,
    ]);
    $adelaide = Branch::factory()->create([
        'latitude' => -34.9285,
        'longitude' => 138.6007,
        'status' => true,
    ]);
    BranchArea::factory()->forBranch($sydney)->create([
        'polygon' => Polygon::squareAround(-33.8688, 151.2093, 0.01),
        'is_active' => true,
    ]);

    $match = app(AreaBranchResolver::class)->resolveForCheckout(-34.9285, 138.6007);

    expect($match)->not->toBeNull()
        ->and($match['branch']->id)->toBe($adelaide->id)
        ->and($match['allocation'])->toBe('nearest_active');
});

test('resolveForCheckout prefers area-linked nearest when the pin is inside an area', function () {
    $areaBranch = Branch::factory()->create([
        'latitude' => -33.9,
        'longitude' => 151.25,
        'status' => true,
    ]);
    $closerWithoutArea = Branch::factory()->create([
        'latitude' => -33.8688,
        'longitude' => 151.2093,
        'status' => true,
    ]);
    BranchArea::factory()->forBranch($areaBranch)->create([
        'polygon' => Polygon::squareAround(-33.8688, 151.2093, 0.05),
        'is_active' => true,
    ]);

    $match = app(AreaBranchResolver::class)->resolveForCheckout(-33.8688, 151.2093);

    expect($match)->not->toBeNull()
        ->and($match['branch']->id)->toBe($areaBranch->id)
        ->and($match['allocation'])->toBe('area_nearest')
        ->and($match['branch']->id)->not->toBe($closerWithoutArea->id);
});

test('resolveForSameDay prefers a zone branch inside the radius over a closer branch from another zone', function () {
    $zoneBranch = Branch::factory()->create(['latitude' => -33.9, 'longitude' => 151.25, 'status' => true]);
    $otherZoneBranch = Branch::factory()->create(['latitude' => -33.87, 'longitude' => 151.21, 'status' => true]);
    BranchArea::factory()->forBranch($zoneBranch)->create([
        'polygon' => Polygon::squareAround(-33.8688, 151.2093, 0.05),
        'is_active' => true,
    ]);
    BranchArea::factory()->forBranch($otherZoneBranch)->create([
        'polygon' => Polygon::squareAround(-30.0, 150.0, 0.05),
        'is_active' => true,
    ]);

    $match = app(AreaBranchResolver::class)->resolveForSameDay(-33.8688, 151.2093, 15);

    expect($match['branch']->id)->toBe($zoneBranch->id)
        ->and($match['allocation'])->toBe('same_day_area_radius');
});

test('resolveForSameDay uses the nearest branch from any zone when the zone branch is outside the radius', function () {
    $farZoneBranch = Branch::factory()->create(['latitude' => -34.5, 'longitude' => 151.2, 'status' => true]);
    $nearOtherZoneBranch = Branch::factory()->create(['latitude' => -33.87, 'longitude' => 151.21, 'status' => true]);
    BranchArea::factory()->forBranch($farZoneBranch)->create([
        'polygon' => Polygon::squareAround(-33.8688, 151.2093, 0.05),
        'is_active' => true,
    ]);
    BranchArea::factory()->forBranch($nearOtherZoneBranch)->create([
        'polygon' => Polygon::squareAround(-30.0, 150.0, 0.05),
        'is_active' => true,
    ]);

    $match = app(AreaBranchResolver::class)->resolveForSameDay(-33.8688, 151.2093, 15);

    expect($match['branch']->id)->toBe($nearOtherZoneBranch->id)
        ->and($match['allocation'])->toBe('same_day_radius');
});

test('resolveForSameDay falls back to the normal zone pick when no branch is inside the radius', function () {
    $farZoneBranch = Branch::factory()->create(['latitude' => -34.5, 'longitude' => 151.2, 'status' => true]);
    Branch::factory()->create(['latitude' => -35.5, 'longitude' => 150.0, 'status' => true]);
    BranchArea::factory()->forBranch($farZoneBranch)->create([
        'polygon' => Polygon::squareAround(-33.8688, 151.2093, 0.05),
        'is_active' => true,
    ]);

    $match = app(AreaBranchResolver::class)->resolveForSameDay(-33.8688, 151.2093, 15);

    expect($match['branch']->id)->toBe($farZoneBranch->id)
        ->and($match['allocation'])->toBe('area_nearest');
});

test('resolveForSameDay returns null when there is no active branch at all', function () {
    expect(app(AreaBranchResolver::class)->resolveForSameDay(-33.8688, 151.2093, 15))->toBeNull();
});
