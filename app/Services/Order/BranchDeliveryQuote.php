<?php

namespace App\Services\Order;

use App\Contracts\DistanceCalculator;
use App\Models\Branch;

/**
 * Distance and delivery fee from one branch to a drop-off point.
 */
class BranchDeliveryQuote
{
    public function __construct(
        private DistanceCalculator $distance,
        private ShippingCalculator $shipping,
    ) {}

    /**
     * @return array{distance_km: float|null, fee: float}
     */
    public function quote(Branch $branch, float|string|null $latitude, float|string|null $longitude): array
    {
        $distanceKm = $this->distanceKm($branch, $latitude, $longitude);

        return [
            'distance_km' => $distanceKm,
            'fee' => $this->shipping->cost($branch, $distanceKm),
        ];
    }

    private function distanceKm(Branch $branch, float|string|null $latitude, float|string|null $longitude): ?float
    {
        if (
            ! is_numeric($latitude) || ! is_numeric($longitude)
            || ! is_numeric($branch->latitude) || ! is_numeric($branch->longitude)
        ) {
            return null;
        }

        return $this->distance->distanceKm(
            (float) $branch->latitude,
            (float) $branch->longitude,
            (float) $latitude,
            (float) $longitude,
        );
    }
}
