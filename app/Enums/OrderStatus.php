<?php

namespace App\Enums;

enum OrderStatus: string
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
        return FulfillmentStatus::from($this->value)->label();
    }

    public function customerLabel(): string
    {
        return FulfillmentStatus::from($this->value)->customerLabel();
    }

    public function allowsCustomerCancel(): bool
    {
        return FulfillmentStatus::from($this->value)->allowsCustomerCancel();
    }

    public function isTerminal(): bool
    {
        return FulfillmentStatus::from($this->value)->isTerminal();
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
