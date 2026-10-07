<?php

namespace App\Support\Driver;

use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Models\PaymentGatewaySetting;

/**
 * Resolved driver field-sale rules.
 *
 * A van sale is delivered when it is placed. Cash on delivery stays due until
 * collected; card and payment-link sales are collected by the selling driver.
 * Stock and the fulfillment branch are the driver's single active assignment.
 * Cancelling a non-terminal driver sale returns bag stock; a delivered sale
 * cannot be cancelled.
 */
final class DriverSaleRules
{
    public static function orderStatus(): OrderStatus
    {
        return OrderStatus::Delivered;
    }

    public static function fulfillmentStatus(): FulfillmentStatus
    {
        return FulfillmentStatus::Delivered;
    }

    public static function collectsImmediately(string $paymentMethod): bool
    {
        return $paymentMethod !== PaymentGatewaySetting::KEY_COD;
    }
}
