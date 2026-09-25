<?php

namespace App\Enums;

enum NotificationType: string
{
    case Order = 'order';
    case Payment = 'payment';
    case Delivery = 'delivery';
    case Promo = 'promo';
    case Security = 'security';
}
