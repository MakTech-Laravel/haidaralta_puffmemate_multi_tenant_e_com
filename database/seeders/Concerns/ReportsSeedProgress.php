<?php

namespace Database\Seeders\Concerns;

use Symfony\Component\Console\Helper\ProgressBar;

/**
 * Live "current/total" progress output for long-running seeders so `db:seed` never looks stuck.
 * Silently no-ops when the seeder runs without a console command attached.
 */
trait ReportsSeedProgress
{
    private ?ProgressBar $seedProgressBar = null;

    protected function startSeedProgress(string $label, int $total): void
    {
        $output = $this->command?->getOutput();

        if ($output === null) {
            return;
        }

        $bar = $output->createProgressBar($total);
        $bar->setFormat('  %message% %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%');
        $bar->setMessage(str_pad($label, 12));
        $bar->start();

        $this->seedProgressBar = $bar;
    }

    protected function advanceSeedProgress(int $step = 1): void
    {
        $this->seedProgressBar?->advance($step);
    }

    protected function finishSeedProgress(?string $summary = null): void
    {
        if ($this->seedProgressBar !== null) {
            $this->seedProgressBar->finish();
            $this->seedProgressBar = null;
            $this->command?->getOutput()->newLine();
        }

        if ($summary !== null) {
            $this->command?->info('  '.$summary);
        }
    }
}
