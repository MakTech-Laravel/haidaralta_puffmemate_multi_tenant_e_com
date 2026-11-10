<?php

namespace App\Console\Commands;

use App\Models\DriverLocation;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('driver-locations:prune')]
#[Description('Delete driver location breadcrumbs older than the configured retention period')]
class PruneDriverLocations extends Command
{
    public function handle(): int
    {
        $cutoff = now()->subDays((int) config('drivers.tracking.retention_days', 30));
        $deleted = 0;

        do {
            $batch = DriverLocation::query()->where('recorded_at', '<', $cutoff)->limit(5000)->delete();
            $deleted += $batch;
        } while ($batch > 0);

        $this->info("Pruned {$deleted} driver location breadcrumb(s).");

        return self::SUCCESS;
    }
}
