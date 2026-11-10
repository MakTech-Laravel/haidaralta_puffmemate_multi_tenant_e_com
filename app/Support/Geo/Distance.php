<?php

namespace App\Support\Geo;

final class Distance
{
    private const EARTH_RADIUS_METERS = 6371000.0;

    /**
     * Great-circle distance between two coordinates, in metres.
     */
    public static function meters(float $latitudeA, float $longitudeA, float $latitudeB, float $longitudeB): float
    {
        $latDelta = deg2rad($latitudeB - $latitudeA);
        $lngDelta = deg2rad($longitudeB - $longitudeA);

        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($latitudeA)) * cos(deg2rad($latitudeB)) * sin($lngDelta / 2) ** 2;

        return self::EARTH_RADIUS_METERS * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
