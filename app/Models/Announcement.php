<?php

namespace App\Models;

use App\Enums\AnnouncementSendMode;
use App\Enums\AnnouncementType;
use Database\Factories\AnnouncementFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property AnnouncementSendMode $send_mode
 * @property list<string> $audiences
 * @property AnnouncementType $type
 * @property string $icon
 * @property string $title
 * @property string $message
 * @property string|null $link_label
 * @property string|null $link_url
 * @property bool $via_phone
 * @property bool $via_email
 * @property bool $via_push
 * @property bool $via_system
 * @property int|null $created_by
 * @property int|null $notification_id
 * @property int $recipient_count
 * @property Carbon|null $sent_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Announcement extends Model
{
    /** @use HasFactory<AnnouncementFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'send_mode',
        'audiences',
        'type',
        'icon',
        'title',
        'message',
        'link_label',
        'link_url',
        'via_phone',
        'via_email',
        'via_push',
        'via_system',
        'created_by',
        'notification_id',
        'recipient_count',
        'sent_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'send_mode' => AnnouncementSendMode::class,
            'audiences' => 'array',
            'type' => AnnouncementType::class,
            'via_phone' => 'boolean',
            'via_email' => 'boolean',
            'via_push' => 'boolean',
            'via_system' => 'boolean',
            'recipient_count' => 'integer',
            'sent_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<Notification, $this>
     */
    public function notification(): BelongsTo
    {
        return $this->belongsTo(Notification::class);
    }
}
