<?php

namespace App\Enums;

enum ContactInquiryStatus: string
{
    case New = 'new';
    case Read = 'read';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Read => 'Read',
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
