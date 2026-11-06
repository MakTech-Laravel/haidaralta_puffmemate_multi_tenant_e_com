<?php

namespace App\Services\Maps;

use App\Enums\MapCapability;
use App\Models\MapProviderUsage;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Cache;

/**
 * Counts billable server-side map calls per provider so quotas can trigger a fallback.
 */
class MapUsageRecorder
{
    private const TOTALS_TTL_SECONDS = 30;

    public function record(string $provider, MapCapability $capability): void
    {
        $today = now()->toDateString();

        $scope = fn () => MapProviderUsage::query()
            ->where('provider', $provider)
            ->where('capability', $capability->value)
            ->where('used_on', $today);

        if ($scope()->increment('request_count') === 0) {
            try {
                MapProviderUsage::query()->create([
                    'provider' => $provider,
                    'capability' => $capability->value,
                    'used_on' => $today,
                    'request_count' => 1,
                ]);
            } catch (UniqueConstraintViolationException) {
                $scope()->increment('request_count');
            }
        }

        Cache::forget($this->totalKey($provider, 'day'));
        Cache::forget($this->totalKey($provider, 'month'));
    }

    public function dailyTotal(string $provider): int
    {
        return (int) Cache::remember(
            $this->totalKey($provider, 'day'),
            self::TOTALS_TTL_SECONDS,
            fn (): int => (int) MapProviderUsage::query()
                ->where('provider', $provider)
                ->where('used_on', now()->toDateString())
                ->sum('request_count'),
        );
    }

    public function monthlyTotal(string $provider): int
    {
        return (int) Cache::remember(
            $this->totalKey($provider, 'month'),
            self::TOTALS_TTL_SECONDS,
            fn (): int => (int) MapProviderUsage::query()
                ->where('provider', $provider)
                ->whereBetween('used_on', [now()->startOfMonth()->toDateString(), now()->toDateString()])
                ->sum('request_count'),
        );
    }

    private function totalKey(string $provider, string $period): string
    {
        return "maps.usage.{$period}.{$provider}.".now()->format($period === 'day' ? 'Y-m-d' : 'Y-m');
    }
}
