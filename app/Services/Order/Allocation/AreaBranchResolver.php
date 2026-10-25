<?php

namespace App\Services\Order\Allocation;

use App\Contracts\DistanceCalculator;
use App\Models\Branch;
use App\Models\BranchArea;
use App\Support\Geo\Polygon;
use Illuminate\Support\Collection;

/**
 * Picks the nearest active branch for cart checkout: prefer branches linked to
 * an active BranchArea whose polygon contains the pin; otherwise nearest active
 * branch by distance (Australia gate lives in AreaCheckoutAllocator).
 */
class AreaBranchResolver
{
    public function __construct(private DistanceCalculator $distance) {}

    /**
     * @return array{branch: Branch, distance_km: float, allocation: string}|null
     */
    public function resolveForCheckout(float $latitude, float $longitude): ?array
    {
        $areaMatch = $this->nearest($latitude, $longitude);

        if ($areaMatch !== null) {
            return [
                ...$areaMatch,
                'allocation' => 'area_nearest',
            ];
        }

        $fallback = $this->nearestActiveBranch($latitude, $longitude);

        if ($fallback === null) {
            return null;
        }

        return [
            ...$fallback,
            'allocation' => 'nearest_active',
        ];
    }

    /**
     * @return array{branch: Branch, distance_km: float}|null
     */
    public function nearest(float $latitude, float $longitude): ?array
    {
        return $this->pickNearest($this->matchingBranches($latitude, $longitude), $latitude, $longitude);
    }

    /**
     * Nearest active branch with coordinates (area membership ignored).
     *
     * @return array{branch: Branch, distance_km: float}|null
     */
    public function nearestActiveBranch(float $latitude, float $longitude): ?array
    {
        $branches = Branch::query()
            ->where('status', true)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();

        return $this->pickNearest($branches, $latitude, $longitude);
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

    /**
     * @param  Collection<int, Branch>  $branches
     * @return array{branch: Branch, distance_km: float}|null
     */
    private function pickNearest(Collection $branches, float $latitude, float $longitude): ?array
    {
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
}
