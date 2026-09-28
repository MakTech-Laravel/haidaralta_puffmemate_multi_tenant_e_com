<?php

namespace Database\Factories;

use App\Enums\AnnouncementSendMode;
use App\Enums\AnnouncementType;
use App\Models\Announcement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Announcement>
 */
class AnnouncementFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'send_mode' => AnnouncementSendMode::Group,
            'audiences' => ['platform'],
            'type' => AnnouncementType::Info,
            'icon' => 'bell',
            'title' => 'Store update',
            'message' => 'A short announcement for the team.',
            'link_label' => null,
            'link_url' => null,
            'via_phone' => false,
            'via_email' => false,
            'via_push' => false,
            'via_system' => true,
            'created_by' => null,
            'notification_id' => null,
            'recipient_count' => 0,
            'sent_at' => null,
        ];
    }
}
