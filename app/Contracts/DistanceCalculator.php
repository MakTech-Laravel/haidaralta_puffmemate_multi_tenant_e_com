<?php

namespace App\Contracts;

use App\Providers\AppServiceProvider;

/**
 * Straight-line/road distance between two coordinates, in kilometres.
 *
 * Kept provider-independent so branch allocation and shipping never depend
 * directly on a mapping vendor. Swap the bound implementation (see
 * {@see AppServiceProvider}) for a Google/Mapbox-backed one
 * later without touching any consumer.
 */
interface DistanceCalculator
{
    public function distanceKm(float $latitudeA, float $longitudeA, float $latitudeB, float $longitudeB): float;
}
