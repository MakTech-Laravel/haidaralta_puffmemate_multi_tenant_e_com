<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case PendingCollection = 'pending_collection';
    case Collected = 'collected';
    case Verified = 'verified';
    case Failed = 'failed';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::PendingCollection => 'Pending collection',
            self::Collected => 'Collected',
            self::Verified => 'Verified',
            self::Failed => 'Failed',
            self::Refunded => 'Refunded',
        };
    }

    public function customerLabel(): string
    {
        return match ($this) {
            self::PendingCollection => 'Payment due on delivery',
            self::Collected, self::Verified => 'Payment received',
            self::Failed => 'Payment unsuccessful',
            self::Refunded => 'Refunded',
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
