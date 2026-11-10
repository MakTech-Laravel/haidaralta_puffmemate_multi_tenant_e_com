<?php

namespace App\Support\Geo;

/**
 * Rough mainland + Tasmania bounding box for storefront delivery gating.
 * Not a cadastral border — rejects clear non-AU pins (including northern-hemisphere mistakes).
 */
class Australia
{
    public const MIN_LATITUDE = -44.0;

    public const MAX_LATITUDE = -10.0;

    public const MIN_LONGITUDE = 112.0;

    public const MAX_LONGITUDE = 154.0;

    public static function contains(float $latitude, float $longitude): bool
    {
        return $latitude >= self::MIN_LATITUDE
            && $latitude <= self::MAX_LATITUDE
            && $longitude >= self::MIN_LONGITUDE
            && $longitude <= self::MAX_LONGITUDE;
    }
}
