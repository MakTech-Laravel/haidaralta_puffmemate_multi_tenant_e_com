<?php

namespace App\Services\Order\Allocation;

use App\Contracts\DistanceCalculator;
use App\Models\Branch;
use Illuminate\Support\Collection;

/**
 * Precomputes every customer-to-branch and branch-to-branch distance once
 * per allocation call. Replaces the previous design where the transfer
 * planner re-sorted the full branch list by distance for every missing
 * cart line, for every candidate destination branch (O(branches x lines x
 * branches log branches) of repeated Haversine calls and sorting).
 */
class BranchDistanceMatrix
{
    /** @var array<string, float|null> */
    private array $branchPairs = [];

    /** @var array<int, float|null> */
    private array $customerDistances = [];

    public function __construct(private DistanceCalculator $distance) {}

    /**
     * @param  Collection<int, Branch>  $branches
     */
    public function build(Collection $branches, float $customerLatitude, float $customerLongitude): void
    {
        $this->branchPairs = [];
        $this->customerDistances = [];

        foreach ($branches as $branch) {
            $this->customerDistances[$branch->id] = $this->calculate(
                $customerLatitude,
                $customerLongitude,
                $branch->latitude === null ? null : (float) $branch->latitude,
                $branch->longitude === null ? null : (float) $branch->longitude,
            );
        }

        foreach ($branches as $from) {
            foreach ($branches as $to) {
                if ($from->id === $to->id) {
                    continue;
                }

                $key = $this->pairKey($from->id, $to->id);

                if (array_key_exists($key, $this->branchPairs)) {
                    continue;
                }

                $this->branchPairs[$key] = $this->calculate(
                    $from->latitude === null ? null : (float) $from->latitude,
                    $from->longitude === null ? null : (float) $from->longitude,
                    $to->latitude === null ? null : (float) $to->latitude,
                    $to->longitude === null ? null : (float) $to->longitude,
                );
            }
        }
    }

    public function customerDistanceTo(int $branchId): ?float
    {
        return $this->customerDistances[$branchId] ?? null;
    }

    public function between(int $branchIdA, int $branchIdB): ?float
    {
        return $this->branchPairs[$this->pairKey($branchIdA, $branchIdB)] ?? null;
    }

    /**
     * Sorts the given branches by distance from the destination branch, nearest
     * first, using the precomputed matrix (no Haversine calls at sort time).
     * Branches with an unknown distance sort last. Intended to be called once
     * per destination branch and reused across every missing cart line.
     *
     * @param  Collection<int, Branch>  $branches
     * @return Collection<int, Branch>
     */
    public function sortByDistanceFrom(int $destinationBranchId, Collection $branches): Collection
    {
        return $branches
            ->filter(fn (Branch $branch): bool => $branch->id !== $destinationBranchId)
            ->sortBy(fn (Branch $branch): float => $this->between($destinationBranchId, $branch->id) ?? PHP_FLOAT_MAX)
            ->values();
    }

    private function pairKey(int $branchIdA, int $branchIdB): string
    {
        return $branchIdA < $branchIdB ? $branchIdA.':'.$branchIdB : $branchIdB.':'.$branchIdA;
    }

    private function calculate(?float $latitudeA, ?float $longitudeA, ?float $latitudeB, ?float $longitudeB): ?float
    {
        if ($latitudeA === null || $longitudeA === null || $latitudeB === null || $longitudeB === null) {
            return null;
        }

        return $this->distance->distanceKm($latitudeA, $longitudeA, $latitudeB, $longitudeB);
    }
}
