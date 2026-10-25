<?php

namespace App\Support\Geo;

/**
 * Simple geographic helpers for closed lat/lng rings used by BranchArea polygons.
 */
class Polygon
{
    /**
     * @param  list<array{lat: float|int|string, lng: float|int|string}>  $ring
     * @return list<array{lat: float, lng: float}>
     */
    public static function normalize(array $ring): array
    {
        $points = [];

        foreach ($ring as $point) {
            if (! is_array($point)) {
                continue;
            }

            $lat = isset($point['lat']) ? (float) $point['lat'] : null;
            $lng = isset($point['lng']) ? (float) $point['lng'] : null;

            if ($lat === null || $lng === null || ! is_finite($lat) || ! is_finite($lng)) {
                continue;
            }

            $points[] = [
                'lat' => round($lat, 7),
                'lng' => round($lng, 7),
            ];
        }

        if ($points === []) {
            return [];
        }

        $first = $points[0];
        $last = $points[array_key_last($points)];

        if ($first['lat'] !== $last['lat'] || $first['lng'] !== $last['lng']) {
            $points[] = $first;
        }

        return array_values($points);
    }

    /**
     * @param  list<array{lat: float|int|string, lng: float|int|string}>  $ring
     * @return array{lat: float, lng: float}|null
     */
    public static function centroid(array $ring): ?array
    {
        $points = self::normalize($ring);

        if (count($points) < 4) {
            return null;
        }

        // Exclude closing duplicate for average.
        $vertices = array_slice($points, 0, -1);
        $count = count($vertices);

        if ($count === 0) {
            return null;
        }

        $sumLat = 0.0;
        $sumLng = 0.0;

        foreach ($vertices as $vertex) {
            $sumLat += $vertex['lat'];
            $sumLng += $vertex['lng'];
        }

        return [
            'lat' => round($sumLat / $count, 7),
            'lng' => round($sumLng / $count, 7),
        ];
    }

    /**
     * Ray-casting point-in-polygon (lat = Y, lng = X).
     *
     * @param  list<array{lat: float|int|string, lng: float|int|string}>  $ring
     */
    public static function contains(float $lat, float $lng, array $ring): bool
    {
        $points = self::normalize($ring);

        if (count($points) < 4) {
            return false;
        }

        $inside = false;
        $count = count($points);

        for ($i = 0, $j = $count - 1; $i < $count; $j = $i++) {
            $yi = $points[$i]['lat'];
            $xi = $points[$i]['lng'];
            $yj = $points[$j]['lat'];
            $xj = $points[$j]['lng'];

            $intersects = (($yi > $lat) !== ($yj > $lat))
                && ($lng < ($xj - $xi) * ($lat - $yi) / (($yj - $yi) ?: 1e-12) + $xi);

            if ($intersects) {
                $inside = ! $inside;
            }
        }

        return $inside;
    }

    /**
     * Count unique vertices (ignoring closing duplicate).
     *
     * @param  list<array{lat: float|int|string, lng: float|int|string}>  $ring
     */
    public static function uniqueVertexCount(array $ring): int
    {
        $points = self::normalize($ring);

        if ($points === []) {
            return 0;
        }

        return max(0, count($points) - 1);
    }

    /**
     * Build a closed square ring around a pin (±delta degrees).
     *
     * @return list<array{lat: float, lng: float}>
     */
    public static function squareAround(float $lat, float $lng, float $delta = 0.01): array
    {
        return self::normalize([
            ['lat' => $lat - $delta, 'lng' => $lng - $delta],
            ['lat' => $lat - $delta, 'lng' => $lng + $delta],
            ['lat' => $lat + $delta, 'lng' => $lng + $delta],
            ['lat' => $lat + $delta, 'lng' => $lng - $delta],
        ]);
    }
}
