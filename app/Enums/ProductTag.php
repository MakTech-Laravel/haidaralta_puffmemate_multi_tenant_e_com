<?php

namespace App\Enums;

enum ProductTag: string
{
    case NewArrival = 'new_arrival';
    case BestSelling = 'best_selling';
    case Popular = 'popular';
    case OnOffer = 'on_offer';

    public function label(): string
    {
        return match ($this) {
            self::NewArrival => 'New arrival',
            self::BestSelling => 'Best selling',
            self::Popular => 'Popular',
            self::OnOffer => 'On offer',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
