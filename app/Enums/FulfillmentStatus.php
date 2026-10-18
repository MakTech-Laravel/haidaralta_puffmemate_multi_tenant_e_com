<?php

namespace App\Enums;

enum FulfillmentStatus: string
{
    case Pending = 'pending';
    case Placed = 'placed';
    case Hold = 'hold';
    case Processing = 'processing';
    case Ready = 'ready';
    case Assigned = 'assigned';
    case PickedUp = 'picked_up';
    case OutForDelivery = 'out_for_delivery';
    case Arrived = 'arrived';
    case DeliveryFailed = 'delivery_failed';
    case Returning = 'returning';
    case Returned = 'returned';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Placed => 'Placed',
            self::Hold => 'On hold',
            self::Processing => 'Processing',
            self::Ready => 'Ready',
            self::Assigned => 'Assigned',
            self::PickedUp => 'Picked up',
            self::OutForDelivery => 'On the way',
            self::Arrived => 'Arrived',
            self::DeliveryFailed => 'Not delivered',
            self::Returning => 'Returning',
            self::Returned => 'Returned',
            self::Delivered => 'Delivered',
            self::Cancelled => 'Cancelled',
        };
    }

    public function customerLabel(): string
    {
        return match ($this) {
            self::Pending => 'Awaiting confirmation',
            self::Placed => 'Order placed',
            self::Hold => 'On hold',
            self::Processing => 'Preparing your order',
            self::Ready => 'Packed',
            self::Assigned => 'Driver assigned',
            self::PickedUp => 'Order picked up',
            self::OutForDelivery => 'On the way',
            self::Arrived => 'Arrived',
            self::DeliveryFailed => 'Delivery unsuccessful',
            self::Returning => 'Returning your order',
            self::Returned => 'Returned to the branch',
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
            self::Assigned, self::PickedUp, self::OutForDelivery, self::Arrived, self::DeliveryFailed, self::Returning => true,
            default => false,
        };
    }

    /**
     * @return list<self>
     */
    public static function driverWorkload(): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $status): bool => $status->holdsDriverWorkload(),
        ));
    }

    /**
     * The next field step a driver takes from this status.
     *
     * @return array{key: string, label: string}|null
     */
    public function driverNextAction(): ?array
    {
        return match ($this) {
            self::Assigned => ['key' => 'pick_up', 'label' => 'Pick up order'],
            self::PickedUp => ['key' => 'start', 'label' => 'Start delivery'],
            self::OutForDelivery => ['key' => 'arrive', 'label' => 'Mark arrived'],
            self::Arrived => ['key' => 'complete', 'label' => 'Complete delivery'],
            self::DeliveryFailed => ['key' => 'return', 'label' => 'Return to branch'],
            default => null,
        };
    }

    public function allowsCustomerCancel(): bool
    {
        return match ($this) {
            self::Pending, self::Hold, self::Placed, self::Processing => true,
            default => false,
        };
    }

    public function allowsOrderChat(): bool
    {
        return match ($this) {
            self::OutForDelivery, self::Arrived, self::DeliveryFailed, self::Returning, self::Delivered => true,
            default => false,
        };
    }

    public function allowsPlacementCode(): bool
    {
        return match ($this) {
            self::Placed, self::Processing, self::Ready, self::Assigned, self::PickedUp, self::OutForDelivery, self::Arrived => true,
            default => false,
        };
    }

    public function allowsStaffCancel(): bool
    {
        return match ($this) {
            self::Pending, self::Hold, self::Placed, self::Processing, self::Ready, self::Assigned, self::Returned => true,
            default => false,
        };
    }

    /**
     * Statuses staff may set through the generic transition endpoint.
     * Assignment, handover OTP, and defect returns use dedicated services.
     *
     * @return list<self>
     */
    public function staffNext(): array
    {
        return match ($this) {
            self::Pending, self::Hold => [self::Placed, self::Cancelled],
            self::Placed => [self::Processing, self::Cancelled],
            self::Processing => [self::Ready, self::Cancelled],
            self::Ready, self::Assigned, self::Returned => [self::Cancelled],
            self::PickedUp => [self::OutForDelivery],
            self::OutForDelivery => [self::Arrived],
            self::DeliveryFailed => [self::Returning],
            self::Returning => [self::Returned],
            default => [],
        };
    }

    /**
     * @return list<array{key: string, label: string, status: string}>
     */
    public function staffActions(): array
    {
        return array_map(
            fn (self $status): array => [
                'key' => $status->value,
                'label' => match ($status) {
                    self::Placed => 'Confirm order',
                    self::Processing => 'Start packaging',
                    self::Ready => 'Packaging complete',
                    self::OutForDelivery => 'On the way',
                    self::Arrived => 'Mark arrived',
                    self::Returning => 'Start return',
                    self::Returned => 'Goods received',
                    self::Cancelled => 'Cancel order',
                    default => $status->label(),
                },
                'status' => $status->value,
            ],
            array_values(array_filter(
                $this->staffNext(),
                fn (self $status): bool => $status !== self::Cancelled,
            )),
        );
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
