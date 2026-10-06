<?php

namespace App\Enums;

enum PackOfferType: string
{
    case Fixed = 'fixed';
    case Percent = 'percent';
    case PackPrice = 'pack_price';
}
