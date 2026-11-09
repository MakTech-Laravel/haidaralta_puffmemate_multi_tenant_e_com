<?php

namespace App\Support\Geo;

final class EncodedPolyline
{
    /**
     * Decodes a Google-style encoded polyline (precision 5) into [latitude, longitude] pairs.
     *
     * @return list<array{0: float, 1: float}>
     */
    public static function decode(string $encoded): array
    {
        $points = [];
        $index = 0;
        $length = strlen($encoded);
        $latitude = 0;
        $longitude = 0;

        while ($index < $length) {
            foreach ([&$latitude, &$longitude] as &$coordinate) {
                $shift = 0;
                $result = 0;

                do {
                    $byte = ord($encoded[$index++]) - 63;
                    $result |= ($byte & 0x1F) << $shift;
                    $shift += 5;
                } while ($byte >= 0x20 && $index < $length);

                $coordinate += ($result & 1) ? ~($result >> 1) : ($result >> 1);
            }
            unset($coordinate);

            $points[] = [$latitude / 1e5, $longitude / 1e5];
        }

        return $points;
    }
}
