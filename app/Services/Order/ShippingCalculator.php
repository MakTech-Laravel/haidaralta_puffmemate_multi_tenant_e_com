<?php

namespace App\Services\Order;

use App\Models\Branch;
use App\Models\BranchShippingRule;

/**
 * Per-branch delivery fee for a computed distance.
 *
 * Reads {@see BranchShippingRule} when the branch has one
 * configured, otherwise falls back to the app-wide defaults in
 * config/catalog.php.
 */
class ShippingCalculator
{
    public function cost(Branch $branch, ?float $distanceKm): float
    {
        $rule = $branch->shippingRule;
        $distanceKm ??= 0.0;

        $baseFee = $rule !== null ? (float) $rule->base_fee : (float) config('catalog.default_shipping_base_fee');
        $costPerKm = $rule !== null ? (float) $rule->cost_per_km : (float) config('catalog.default_shipping_cost_per_km');
        $minimumCharge = $rule !== null ? (float) $rule->minimum_delivery_charge : (float) config('catalog.default_shipping_minimum_charge');
        $freeDistanceKm = $rule?->free_delivery_distance_km !== null
            ? (float) $rule->free_delivery_distance_km
            : (float) config('catalog.default_shipping_free_distance_km');

        if ($freeDistanceKm > 0 && $distanceKm <= $freeDistanceKm) {
            return 0.0;
        }

        $billableKm = max(0.0, $distanceKm - max($freeDistanceKm, 0.0));

        return round(max($minimumCharge, $baseFee + ($costPerKm * $billableKm)), 2);
    }
}
