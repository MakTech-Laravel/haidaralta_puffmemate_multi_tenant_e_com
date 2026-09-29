<?php

namespace App\Services\Order\Allocation;

/**
 * Sorts scored candidates and returns the winner, plus the full ranked list
 * so callers (e.g. an explainability recorder) can see what was considered.
 */
class BranchSelector
{
    public function __construct(private AllocationScoreCalculator $score) {}

    /**
     * @param  list<array<string, mixed>>  $candidates
     * @return list<array<string, mixed>> sorted, winner first
     */
    public function rank(array $candidates): array
    {
        usort($candidates, fn (array $left, array $right): int => $this->score->compare($left, $right));

        return $candidates;
    }

    /**
     * @param  list<array<string, mixed>>  $candidates
     * @return array<string, mixed>
     */
    public function select(array $candidates): array
    {
        return $this->rank($candidates)[0];
    }
}
