<?php

namespace App\Services\Order\Allocation;

/**
 * Maximum allowed transfer/prep ETA per delivery method, read from config.
 */
class DeliverySLACalculator
{
    public function maxHours(?string $deliveryMethodKey): float
    {
        if ($deliveryMethodKey === 'express' || $deliveryMethodKey === 'same-day') {
            return (float) config(
                'catalog.express_max_transfer_hours',
                config('catalog.same_day_max_transfer_hours', 4),
            );
        }

        return (float) config(
            'catalog.regular_max_transfer_hours',
            config('catalog.australia_wide_max_transfer_hours', 72),
        );
    }

    public function withinSla(float $etaHours, ?string $deliveryMethodKey): bool
    {
        return $etaHours <= $this->maxHours($deliveryMethodKey);
    }
}
