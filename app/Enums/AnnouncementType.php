<?php

namespace App\Enums;

enum AnnouncementType: string
{
    case Info = 'info';
    case Promo = 'promo';
    case Alert = 'alert';
    case Update = 'update';

    public function toNotificationType(): NotificationType
    {
        return match ($this) {
            self::Info => NotificationType::Info,
            self::Promo => NotificationType::Promo,
            self::Alert => NotificationType::Alert,
            self::Update => NotificationType::Update,
        };
    }
}
