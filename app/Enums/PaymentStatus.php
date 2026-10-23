<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case PendingCollection = 'pending_collection';
    case Collected = 'collected';
    case Verified = 'verified';
    case Failed = 'failed';
    case Refunded = 'refunded';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PendingCollection => 'Pending collection',
            self::Collected => 'Collected',
            self::Verified => 'Verified',
            self::Failed => 'Failed',
            self::Refunded => 'Refunded',
            self::Cancelled => 'Cancelled',
        };
    }

    public function customerLabel(): string
    {
        return match ($this) {
            self::PendingCollection => 'Payment due on delivery',
            self::Collected, self::Verified => 'Payment received',
            self::Failed => 'Payment unsuccessful',
            self::Refunded => 'Refunded',
            self::Cancelled => 'Cancelled',
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

    /**
     * Targets a branch or platform user may set through the payment override.
     * `pending_collection` is a placement default, not a manual target.
     *
     * @return list<array{value: string, label: string}>
     */
    public static function actionableOptions(): array
    {
        $cases = array_filter(
            self::cases(),
            fn (self $status): bool => ! in_array($status, [self::PendingCollection, self::Cancelled], true),
        );

        return array_values(array_map(
            fn (self $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
            ],
            $cases,
        ));
    }
}
