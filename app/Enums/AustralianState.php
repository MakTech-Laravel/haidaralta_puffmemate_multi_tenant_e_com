<?php

namespace App\Enums;

enum AustralianState: string
{
    case NewSouthWales = 'nsw';
    case Victoria = 'vic';
    case Queensland = 'qld';
    case WesternAustralia = 'wa';
    case SouthAustralia = 'sa';
    case Tasmania = 'tas';
    case AustralianCapitalTerritory = 'act';
    case NorthernTerritory = 'nt';

    public function label(): string
    {
        return match ($this) {
            self::NewSouthWales => 'New South Wales',
            self::Victoria => 'Victoria',
            self::Queensland => 'Queensland',
            self::WesternAustralia => 'Western Australia',
            self::SouthAustralia => 'South Australia',
            self::Tasmania => 'Tasmania',
            self::AustralianCapitalTerritory => 'Australian Capital Territory',
            self::NorthernTerritory => 'Northern Territory',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $state): array => [
                'value' => $state->value,
                'label' => $state->label(),
            ],
            self::cases(),
        );
    }
}
