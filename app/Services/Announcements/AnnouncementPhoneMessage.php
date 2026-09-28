<?php

namespace App\Services\Announcements;

class AnnouncementPhoneMessage
{
    public static function render(string $title, string $message, ?string $linkUrl = null): string
    {
        return trim(view('sms.announcements.sent', [
            'title' => $title,
            'message' => $message,
            'linkUrl' => $linkUrl,
        ])->render());
    }
}
