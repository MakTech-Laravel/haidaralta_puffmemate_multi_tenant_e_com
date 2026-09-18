<?php

namespace App\Models;

use App\Enums\MessageType;
use Database\Factories\MessageFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Chat rows are deliberately not audited: message volume would flood the activity log.
 *
 * @property int $id
 * @property int $conversation_id
 * @property int|null $sender_id
 * @property MessageType $type
 * @property string|null $body
 * @property Carbon|null $created_at
 * @property-read User|null $sender
 * @property-read Collection<int, MessageAttachment> $attachments
 */
class Message extends Model
{
    /** @use HasFactory<MessageFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'conversation_id',
        'sender_id',
        'type',
        'body',
    ];

    /**
     * @return BelongsTo<Conversation, $this>
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * @return HasMany<MessageAttachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(MessageAttachment::class);
    }

    /**
     * @return HasMany<MessageRead, $this>
     */
    public function reads(): HasMany
    {
        return $this->hasMany(MessageRead::class);
    }

    protected function casts(): array
    {
        return [
            'type' => MessageType::class,
        ];
    }
}
