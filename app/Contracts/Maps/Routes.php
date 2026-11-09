<?php

namespace App\Contracts\Maps;

use App\Services\Maps\Data\RouteResult;

interface Routes
{
    /**
     * @param  array{0: float, 1: float}  $origin  [latitude, longitude]
     * @param  array{0: float, 1: float}  $destination  [latitude, longitude]
     */
    public function route(array $origin, array $destination): ?RouteResult;
}
