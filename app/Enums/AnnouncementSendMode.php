<?php

namespace App\Enums;

enum AnnouncementSendMode: string
{
    case Group = 'group';
    case Individual = 'individual';
}
