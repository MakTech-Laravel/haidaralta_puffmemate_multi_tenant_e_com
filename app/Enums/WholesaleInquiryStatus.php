<?php

namespace App\Enums;

enum WholesaleInquiryStatus: string
{
    case New = 'new';
    case Read = 'read';
    case Converted = 'converted';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Read => 'Read',
            self::Converted => 'Order placed',
            self::Rejected => 'Rejected',
        };
    }

    public function customerLabel(): string
    {
        return match ($this) {
            self::New => 'Submitted',
            self::Read => 'Under review',
            self::Converted => 'Order placed',
            self::Rejected => 'Declined',
        };
    }

    /**
     * Converted and rejected inquiries are closed; no further order or rejection.
     */
    public function isOpen(): bool
    {
        return match ($this) {
            self::New, self::Read => true,
            self::Converted, self::Rejected => false,
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
