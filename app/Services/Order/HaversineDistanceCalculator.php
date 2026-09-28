<?php

namespace App\Services\Order;

use App\Contracts\DistanceCalculator;

/**
 * Straight-line great-circle distance. Pure math, zero external calls.
 */
class HaversineDistanceCalculator implements DistanceCalculator
{
    private const EARTH_RADIUS_KM = 6371.0;

    public function distanceKm(float $latitudeA, float $longitudeA, float $latitudeB, float $longitudeB): float
    {
        $latDelta = deg2rad($latitudeB - $latitudeA);
        $lngDelta = deg2rad($longitudeB - $longitudeA);

        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($latitudeA)) * cos(deg2rad($latitudeB)) * sin($lngDelta / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round(self::EARTH_RADIUS_KM * $c, 2);
    }
}
