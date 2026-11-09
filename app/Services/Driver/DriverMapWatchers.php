<?php

namespace App\Services\Driver;

use Illuminate\Support\Facades\Cache;

/**
 * Remembers which live maps are open right now. Driver positions are only pushed over Reverb
 * while someone is watching; the latest position is always cached, so a map that opens later
 * still starts from a full picture.
 */
class DriverMapWatchers
{
    /** Open maps ping every ~30 s; the flag expires on its own once they stop. */
    private const TTL_SECONDS = 60;

    public function touchPlatform(): void
    {
        Cache::put('driver-map.watch.platform', 1, self::TTL_SECONDS);
    }

    public function touchBranch(int $branchId): void
    {
        Cache::put("driver-map.watch.branch.{$branchId}", 1, self::TTL_SECONDS);
    }

    /**
     * @param  list<int>  $branchIds  Branches the driver is currently assigned to
     */
    public function isWatched(array $branchIds): bool
    {
        if (Cache::has('driver-map.watch.platform')) {
            return true;
        }

        foreach ($branchIds as $branchId) {
            if (Cache::has("driver-map.watch.branch.{$branchId}")) {
                return true;
            }
        }

        return false;
    }
}
