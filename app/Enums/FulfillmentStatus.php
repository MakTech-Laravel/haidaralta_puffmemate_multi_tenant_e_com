<?php

namespace App\Enums;

enum FulfillmentStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Ready = 'ready';
    case Assigned = 'assigned';
    case PickedUp = 'picked_up';
    case OutForDelivery = 'out_for_delivery';
    case DeliveryFailed = 'delivery_failed';
    case Returning = 'returning';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Processing => 'Processing',
            self::Ready => 'Ready',
            self::Assigned => 'Assigned',
            self::PickedUp => 'Picked up',
            self::OutForDelivery => 'Out for delivery',
            self::DeliveryFailed => 'Delivery failed',
            self::Returning => 'Returning',
            self::Delivered => 'Delivered',
            self::Cancelled => 'Cancelled',
        };
    }

    public function customerLabel(): string
    {
        return match ($this) {
            self::Pending => 'Order Placed',
            self::Processing => 'Preparing Your Order',
            self::Ready => 'Ready For Delivery',
            self::Assigned => 'Driver Assigned',
            self::PickedUp => 'Order Picked Up',
            self::OutForDelivery => 'On The Way',
            self::DeliveryFailed => 'Delivery unsuccessful',
            self::Returning => 'Returning your order',
            self::Delivered => 'Delivered',
            self::Cancelled => 'Cancelled',
        };
    }

    public function isTerminal(): bool
    {
        return match ($this) {
            self::Delivered, self::Cancelled => true,
            default => false,
        };
    }

    public function holdsDriverWorkload(): bool
    {
        return match ($this) {
            self::Assigned, self::PickedUp, self::OutForDelivery, self::DeliveryFailed, self::Returning => true,
            default => false,
        };
    }

    public function allowsCustomerCancel(): bool
    {
        return match ($this) {
            self::Pending, self::Processing => true,
            default => false,
        };
    }

    public function allowsStaffCancel(): bool
    {
        return match ($this) {
            self::Pending, self::Processing, self::Ready, self::Assigned => true,
            default => false,
        };
    }

    /**
     * Packing-desk transitions that staff may post. Assignment, pickup, and
     * delivery completion use dedicated services.
     *
     * @return list<self>
     */
    public function staffNext(): array
    {
        return match ($this) {
            self::Pending => [self::Processing, self::Cancelled],
            self::Processing => [self::Ready, self::Cancelled],
            self::Ready, self::Assigned => [self::Cancelled],
            default => [],
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
            ],
            self::cases(),
        );
    }
}
