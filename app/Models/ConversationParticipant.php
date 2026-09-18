<?php

namespace App\Models;

use App\Enums\ConversationParticipantRole;
use Database\Factories\ConversationParticipantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $conversation_id
 * @property int $user_id
 * @property ConversationParticipantRole $role
 * @property int|null $last_delivered_message_id
 * @property int|null $last_read_message_id
 * @property Carbon|null $joined_at
 * @property-read User $user
 */
class ConversationParticipant extends Model
{
    /** @use HasFactory<ConversationParticipantFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'conversation_id',
        'user_id',
        'role',
        'last_delivered_message_id',
        'last_read_message_id',
        'joined_at',
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
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'role' => ConversationParticipantRole::class,
            'joined_at' => 'datetime',
            'last_delivered_message_id' => 'integer',
            'last_read_message_id' => 'integer',
        ];
    }
}
