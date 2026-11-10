<?php

namespace App\Services\Maps;

use App\Enums\MapCapability;
use App\Enums\NotificationType;
use App\Enums\PermissionEnum;
use App\Models\MapProviderUsage;
use App\Services\Notifications\PermissionRecipientResolver;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Cache;

/**
 * Counts billable server-side map calls per provider so quotas can trigger a fallback.
 */
class MapUsageRecorder
{
    private const TOTALS_TTL_SECONDS = 30;

    /** Share of a limit at which platform admins get a heads-up (100 is always reported too). */
    private const WARN_AT_PERCENT = 80;

    public function __construct(
        private readonly MapSettingsService $settings,
        private readonly PermissionRecipientResolver $recipients,
    ) {}

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

        $this->warnAboutLimits($provider);
    }

    /**
     * Tells platform admins once per period when a provider reaches 80% and 100% of a limit,
     * so a runaway bill or a silent fallback to the backup provider never comes as a surprise.
     */
    private function warnAboutLimits(string $provider): void
    {
        $row = $this->settings->row($provider);

        if ($row === null) {
            return;
        }

        $periods = [
            'day' => ['limit' => $row->daily_limit, 'total' => fn (): int => $this->dailyTotal($provider), 'ttl' => 172800],
            'month' => ['limit' => $row->monthly_limit, 'total' => fn (): int => $this->monthlyTotal($provider), 'ttl' => 3024000],
        ];

        foreach ($periods as $period => $info) {
            if (! $info['limit']) {
                continue;
            }

            $total = $info['total']();
            $percent = (int) floor($total / $info['limit'] * 100);
            $level = $percent >= 100 ? 100 : ($percent >= self::WARN_AT_PERCENT ? self::WARN_AT_PERCENT : null);

            if ($level === null) {
                continue;
            }

            $key = "maps.usage.alert.{$provider}.{$period}.{$level}.".now()->format($period === 'day' ? 'Y-m-d' : 'Y-m');

            if (! Cache::add($key, 1, $info['ttl'])) {
                continue;
            }

            $label = (string) config("maps.providers.{$provider}.label", $provider);
            $periodLabel = $period === 'day' ? 'daily' : 'monthly';

            notify_users(
                $this->recipients->platform(PermissionEnum::MANAGE_APPLICATION_SETTINGS),
                NotificationType::Alert,
                $level === 100 ? "{$label} limit reached" : "{$label} is nearing its limit",
                $level === 100
                    ? "{$label} used all {$info['limit']} of its {$periodLabel} requests. Address search and routing now use the fallback provider."
                    : "{$label} has used {$total} of its {$info['limit']} {$periodLabel} requests ({$percent}%).",
                [
                    'link_url' => route('platform.settings.map-provider.edit'),
                    'link_label' => 'Open map settings',
                ],
            );
        }
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
