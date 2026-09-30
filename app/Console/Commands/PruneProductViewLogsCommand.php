<?php

namespace App\Console\Commands;

use App\Services\Storefront\ProductViewService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('product-views:prune')]
#[Description('Delete product view logs older than seven days without changing products.views')]
class PruneProductViewLogsCommand extends Command
{
    public function handle(ProductViewService $views): int
    {
        $deleted = $views->pruneOlderThanDays(7);

        $this->info("Pruned {$deleted} product view log(s).");

        return self::SUCCESS;
    }
}
