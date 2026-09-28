<?php

namespace App\Enums;

enum NotificationType: string
{
    case Order = 'order';
    case Payment = 'payment';
    case Delivery = 'delivery';
    case Promo = 'promo';
    case Security = 'security';
    case Info = 'info';
    case Alert = 'alert';
    case Update = 'update';
}
