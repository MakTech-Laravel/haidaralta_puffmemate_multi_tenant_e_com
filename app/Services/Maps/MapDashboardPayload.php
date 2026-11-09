<?php

namespace App\Services\Maps;

use App\Enums\MapCapability;

/**
 * Builds the secret-free props for the platform "Map provider" settings page.
 */
class MapDashboardPayload
{
    public function __construct(
        private readonly MapSettingsService $settings,
        private readonly MapProviderCatalog $catalog,
        private readonly MapUsageRecorder $usage,
    ) {}

    /**
     * @return array{
     *     selection: array{display_provider: string, geocoding_provider: string, routing_provider: string, fallback_provider: string},
     *     capabilities: list<array{value: string, label: string, field: string, providers: list<array{key: string, label: string, available: bool}>}>,
     *     providers: list<array<string, mixed>>
     * }
     */
    public function build(): array
    {
        $selection = $this->settings->settings();
        $providers = $this->catalog->all();

        $capabilities = collect(MapCapability::cases())->map(fn (MapCapability $capability): array => [
            'value' => $capability->value,
            'label' => $capability->label(),
            'field' => $capability->value.'_provider',
            'providers' => collect($providers)->map(fn ($provider): array => [
                'key' => $provider->key(),
                'label' => $provider->label(),
                'available' => $provider->supports($capability),
            ])->all(),
        ])->all();

        return [
            'selection' => [
                'display_provider' => $selection->display_provider,
                'geocoding_provider' => $selection->geocoding_provider,
                'routing_provider' => $selection->routing_provider,
                'fallback_provider' => $selection->fallback_provider,
            ],
            'capabilities' => $capabilities,
            'providers' => collect($providers)->map(fn ($provider): array => $this->provider($provider->key()))->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function provider(string $key): array
    {
        $config = (array) config("maps.providers.{$key}");
        $row = $this->settings->row($key);
        $provider = $this->catalog->make($key);

        return [
            'key' => $key,
            'label' => (string) ($config['label'] ?? $key),
            'description' => (string) ($config['description'] ?? ''),
            'is_enabled' => (bool) ($row?->is_enabled ?? true),
            'capabilities' => array_map(
                fn (MapCapability $capability): string => $capability->value,
                $provider?->capabilities() ?? [],
            ),
            'fields' => collect((array) ($config['fields'] ?? []))->map(fn (array $field, string $name): array => [
                'name' => $name,
                'label' => (string) $field['label'],
                'help' => (string) ($field['help'] ?? ''),
                'source' => $this->settings->keySource($key, $name),
            ])->values()->all(),
            'options' => collect((array) ($config['option_labels'] ?? []))->map(fn (string $label, string $name): array => [
                'name' => $name,
                'label' => $label,
                'value' => (string) (($row?->options ?? [])[$name] ?? ''),
                'default' => (string) (($config['options'] ?? [])[$name] ?? ''),
            ])->values()->all(),
            'daily_limit' => $row?->daily_limit,
            'monthly_limit' => $row?->monthly_limit,
            'usage' => [
                'today' => $today = $this->usage->dailyTotal($key),
                'month' => $month = $this->usage->monthlyTotal($key),
                'today_percent' => $row?->daily_limit ? min(100, (int) floor($today / $row->daily_limit * 100)) : null,
                'month_percent' => $row?->monthly_limit ? min(100, (int) floor($month / $row->monthly_limit * 100)) : null,
            ],
        ];
    }
}
