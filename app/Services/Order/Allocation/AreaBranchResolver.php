<?php

namespace App\Services\Order\Allocation;

use App\Contracts\DistanceCalculator;
use App\Models\Branch;
use App\Models\BranchArea;
use App\Support\Geo\Polygon;
use Illuminate\Support\Collection;

/**
 * Picks the nearest active branch whose active BranchArea polygon contains the
 * customer pin. Cart checkout uses this instead of delivery_radius_km.
 */
class AreaBranchResolver
{
    public function __construct(private DistanceCalculator $distance) {}

    /**
     * @return array{branch: Branch, distance_km: float}|null
     */
    public function nearest(float $latitude, float $longitude): ?array
    {
        $branches = $this->matchingBranches($latitude, $longitude);

        if ($branches->isEmpty()) {
            return null;
        }

        $best = null;
        $bestDistance = PHP_FLOAT_MAX;

        foreach ($branches as $branch) {
            if ($branch->latitude === null || $branch->longitude === null) {
                continue;
            }

            $distanceKm = $this->distance->distanceKm(
                $latitude,
                $longitude,
                (float) $branch->latitude,
                (float) $branch->longitude,
            );

            if ($distanceKm < $bestDistance) {
                $best = $branch;
                $bestDistance = $distanceKm;
            }
        }

        if ($best === null) {
            return null;
        }

        return [
            'branch' => $best,
            'distance_km' => round($bestDistance, 2),
        ];
    }

    /**
     * Active branches linked to at least one active area whose polygon contains the pin.
     *
     * @return Collection<int, Branch>
     */
    public function matchingBranches(float $latitude, float $longitude): Collection
    {
        $areas = BranchArea::query()
            ->where('is_active', true)
            ->whereNotNull('polygon')
            ->with(['branches' => fn ($query) => $query->where('status', true)])
            ->get();

        $matched = collect();

        foreach ($areas as $area) {
            $polygon = $area->polygon;

            if (! is_array($polygon) || $polygon === []) {
                continue;
            }

            if (! Polygon::contains($latitude, $longitude, $polygon)) {
                continue;
            }

            foreach ($area->branches as $branch) {
                $matched->put($branch->id, $branch);
            }
        }

        return $matched->values();
    }
}
