<?php

namespace App\Enums;

enum DriverVehicleType: string
{
    case Bicycle = 'bicycle';
    case Motorcycle = 'motorcycle';
    case Car = 'car';
    case Van = 'van';

    public function label(): string
    {
        return match ($this) {
            self::Bicycle => 'Bicycle',
            self::Motorcycle => 'Motorcycle',
            self::Car => 'Car',
            self::Van => 'Van',
        };
    }
}
