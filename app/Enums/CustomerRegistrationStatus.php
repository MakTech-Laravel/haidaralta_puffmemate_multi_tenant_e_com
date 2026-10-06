<?php

namespace App\Enums;

enum CustomerRegistrationStatus: string
{
    case Provisional = 'provisional';
    case Registered = 'registered';

    public function label(): string
    {
        return match ($this) {
            self::Provisional => 'Provisional',
            self::Registered => 'Registered',
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
