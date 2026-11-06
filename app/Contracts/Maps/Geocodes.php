<?php

namespace App\Contracts\Maps;

use App\Services\Maps\Data\GeocodeResult;

interface Geocodes
{
    /**
     * @param  array{country?: string|null, limit?: int}  $options
     * @return list<GeocodeResult>
     */
    public function geocode(string $query, array $options = []): array;

    public function reverse(float $latitude, float $longitude): ?GeocodeResult;
}
