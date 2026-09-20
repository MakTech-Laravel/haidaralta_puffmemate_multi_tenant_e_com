<?php

namespace App\Support;

final class BranchMap
{
    private const BBOX_DELTA = 0.012;

    /**
     * @return array{google: string, open_street_map: string, embed: string}|null
     */
    public static function links(mixed $latitude, mixed $longitude): ?array
    {
        if (! is_numeric($latitude) || ! is_numeric($longitude)) {
            return null;
        }

        $lat = self::format((float) $latitude);
        $lng = self::format((float) $longitude);
        $query = $lat.','.$lng;
        $bbox = implode(',', [
            self::format((float) $lng - self::BBOX_DELTA),
            self::format((float) $lat - self::BBOX_DELTA),
            self::format((float) $lng + self::BBOX_DELTA),
            self::format((float) $lat + self::BBOX_DELTA),
        ]);

        return [
            'google' => 'https://www.google.com/maps?q='.$query,
            'open_street_map' => 'https://www.openstreetmap.org/?mlat='.$lat.'&mlon='.$lng.'#map=16/'.$lat.'/'.$lng,
            'embed' => 'https://www.openstreetmap.org/export/embed.html?bbox='.$bbox.'&layer=mapnik&marker='.$query,
        ];
    }

    private static function format(float $value): string
    {
        return number_format($value, 7, '.', '');
    }
}
