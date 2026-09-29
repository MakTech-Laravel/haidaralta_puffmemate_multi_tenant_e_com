<?php

namespace App\Services\Order\Allocation;

/**
 * Maximum allowed transfer/prep ETA per delivery method, read from config.
 */
class DeliverySLACalculator
{
    public function maxHours(?string $deliveryMethodKey): float
    {
        if ($deliveryMethodKey === 'same-day') {
            return (float) config('catalog.same_day_max_transfer_hours', 4);
        }

        return (float) config('catalog.australia_wide_max_transfer_hours', 72);
    }

    public function withinSla(float $etaHours, ?string $deliveryMethodKey): bool
    {
        return $etaHours <= $this->maxHours($deliveryMethodKey);
    }
}
