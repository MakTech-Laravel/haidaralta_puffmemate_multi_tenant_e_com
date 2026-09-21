<?php

namespace App\Services\Application;

use App\Models\PaymentGatewaySetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class PaymentGatewaySettingsService
{
    public const CACHE_KEY = 'payment_gateway_settings.all';

    /**
     * @return Collection<int, PaymentGatewaySetting>
     */
    /**
     * @return Collection<int, PaymentGatewaySetting>
     */
    public function all(): Collection
    {
        /** @var list<array<string, mixed>> $rows */
        $rows = Cache::rememberForever(self::CACHE_KEY, function (): array {
            $this->ensureDefaults();

            return PaymentGatewaySetting::query()
                ->orderBy('sort')
                ->orderBy('id')
                ->get()
                ->map(fn (PaymentGatewaySetting $method): array => $method->getAttributes())
                ->all();
        });

        return collect($rows)->map(
            fn (array $attributes): PaymentGatewaySetting => (new PaymentGatewaySetting)->newFromBuilder($attributes),
        );
    }

    /**
     * @return Collection<int, PaymentGatewaySetting>
     */
    public function refresh(): Collection
    {
        Cache::forget(self::CACHE_KEY);

        $this->ensureDefaults();

        $methods = PaymentGatewaySetting::query()
            ->orderBy('sort')
            ->orderBy('id')
            ->get();

        Cache::forever(
            self::CACHE_KEY,
            $methods->map(fn (PaymentGatewaySetting $method): array => $method->getAttributes())->all(),
        );

        return $methods;
    }

    /**
     * @return list<array{id: string, label: string, description: string|null, badge: string}>
     */
    public function enabledMethods(): array
    {
        if (! $this->tableReady()) {
            return $this->fallbackMethods();
        }

        return $this->all()
            ->where('enabled', true)
            ->values()
            ->map(fn (PaymentGatewaySetting $method): array => [
                'id' => $method->key,
                'label' => $method->label,
                'title' => $method->label,
                'description' => $method->description ?? '',
                'badge' => match ($method->key) {
                    PaymentGatewaySetting::KEY_COD => '$',
                    PaymentGatewaySetting::KEY_POS => 'POS',
                    PaymentGatewaySetting::KEY_PAYMENT_LINK => 'LINK',
                    default => strtoupper(substr($method->key, 0, 3)),
                },
            ])
            ->all();
    }

    /**
     * @param  list<array{key: string, label: string, description?: string|null, enabled: bool}>  $methods
     * @return Collection<int, PaymentGatewaySetting>
     */
    public function sync(array $methods): Collection
    {
        $this->ensureDefaults();

        foreach ($methods as $index => $method) {
            PaymentGatewaySetting::query()
                ->where('key', $method['key'])
                ->update([
                    'label' => $method['label'],
                    'description' => $method['description'] ?? null,
                    'enabled' => (bool) $method['enabled'],
                    'sort' => $index,
                ]);
        }

        return $this->refresh();
    }

    public function labelFor(string $key, ?string $fallback = null): string
    {
        if (! $this->tableReady()) {
            return $fallback ?? $key;
        }

        $method = $this->all()->firstWhere('key', $key);

        return $method?->label ?? $fallback ?? $key;
    }

    public function ensureDefaults(): void
    {
        if (! $this->tableReady()) {
            return;
        }

        $defaults = [
            [
                'key' => PaymentGatewaySetting::KEY_COD,
                'label' => 'Cash on delivery (COD)',
                'description' => 'Pay the driver in cash',
                'enabled' => true,
                'sort' => 0,
            ],
            [
                'key' => PaymentGatewaySetting::KEY_POS,
                'label' => 'POS / Card on delivery',
                'description' => 'Driver carries a POS/EFTPOS machine',
                'enabled' => true,
                'sort' => 1,
            ],
            [
                'key' => PaymentGatewaySetting::KEY_PAYMENT_LINK,
                'label' => 'Payment link',
                'description' => 'Pay via a shared payment link',
                'enabled' => false,
                'sort' => 2,
            ],
        ];

        foreach ($defaults as $default) {
            PaymentGatewaySetting::query()->firstOrCreate(
                ['key' => $default['key']],
                $default,
            );
        }
    }

    /**
     * @return list<array{id: string, label: string, title: string, description: string, badge: string}>
     */
    private function fallbackMethods(): array
    {
        return [
            [
                'id' => PaymentGatewaySetting::KEY_COD,
                'label' => 'Cash on delivery (COD)',
                'title' => 'Cash on delivery (COD)',
                'description' => 'Pay the driver in cash',
                'badge' => '$',
            ],
            [
                'id' => PaymentGatewaySetting::KEY_POS,
                'label' => 'POS / Card on delivery',
                'title' => 'POS / Card on delivery',
                'description' => 'Driver carries a POS/EFTPOS machine',
                'badge' => 'POS',
            ],
        ];
    }

    private function tableReady(): bool
    {
        try {
            return Schema::hasTable('payment_gateway_settings');
        } catch (\Throwable) {
            return false;
        }
    }
}
