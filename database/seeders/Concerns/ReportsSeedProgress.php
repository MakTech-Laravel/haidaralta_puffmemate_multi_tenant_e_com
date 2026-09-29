<?php

namespace Database\Seeders\Concerns;

use Closure;
use Illuminate\Support\Collection;
use Symfony\Component\Console\Helper\ProgressBar;

/**
 * Live "current/total" progress output for seeders so `db:seed` never looks stuck.
 * Silently no-ops when the seeder runs without a console command attached.
 */
trait ReportsSeedProgress
{
    private ?ProgressBar $seedProgressBar = null;

    /**
     * @var list<Closure(): void>
     */
    private array $queuedSeedSteps = [];

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

    /**
     * @param  string|(Closure(int): string)|null  $summary  Closures run after the work (receiving the step total) so counts are current.
     */
    protected function finishSeedProgress(string|Closure|null $summary = null): void
    {
        $total = $this->seedProgressBar?->getMaxSteps() ?? 0;

        if ($this->seedProgressBar !== null) {
            $this->seedProgressBar->finish();
            $this->seedProgressBar = null;
            $this->command?->getOutput()->newLine();
        }

        if ($summary === null || $this->command === null) {
            return;
        }

        if ($summary instanceof Closure) {
            $summary = $summary($total);
        }

        $this->command->info('  '.$summary);
    }

    /**
     * Run the callback once per item with a progress bar sized to the item count.
     *
     * @template TKey of array-key
     * @template TItem
     *
     * @param  array<TKey, TItem>|Collection<TKey, TItem>  $items
     * @param  Closure(TItem, TKey): mixed  $callback
     * @param  string|(Closure(int): string)|null  $summary
     */
    protected function withSeedProgress(string $label, array|Collection $items, Closure $callback, string|Closure|null $summary = null): void
    {
        $this->startSeedProgress($label, count($items));

        foreach ($items as $key => $item) {
            $callback($item, $key);
            $this->advanceSeedProgress();
        }

        $this->finishSeedProgress($summary);
    }

    /**
     * Defer a unit of work so hand-written seeders built from inline helper calls
     * know their total before the bar starts. Run them with runQueuedSeedSteps().
     *
     * @param  Closure(): void  $step
     */
    protected function queueSeedStep(Closure $step): void
    {
        $this->queuedSeedSteps[] = $step;
    }

    /**
     * @param  string|(Closure(int): string)|null  $summary
     */
    protected function runQueuedSeedSteps(string $label, string|Closure|null $summary = null): void
    {
        $steps = $this->queuedSeedSteps;
        $this->queuedSeedSteps = [];

        $this->withSeedProgress($label, $steps, fn (Closure $step) => $step(), $summary);
    }
}
