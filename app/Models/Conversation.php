<?php

namespace App\Models;

use App\Contracts\ScoutSearchable;
use App\Enums\ConversationStatus;
use App\Models\Concerns\AuditsChanges;
use App\Support\SearchableTimestamp;
use Database\Factories\ConversationFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Laravel\Scout\Searchable;

/**
 * @property int $id
 * @property int $order_id
 * @property int $branch_id
 * @property ConversationStatus $status
 * @property string|null $last_message_preview
 * @property int|null $last_message_sender_id
 * @property Carbon|null $last_message_at
 * @property Carbon|null $closed_at
 * @property-read Order $order
 * @property-read Branch|null $branch
 * @property-read Collection<int, ConversationParticipant> $participants
 */
class Conversation extends Model implements ScoutSearchable
{
    /** @use HasFactory<ConversationFactory> */
    use AuditsChanges, HasFactory, Searchable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'order_id',
        'branch_id',
        'status',
        'last_message_preview',
        'last_message_sender_id',
        'last_message_at',
        'closed_at',
    ];

    /**
     * Free-text inbox search runs through Meilisearch; status and branch
     * filtering stay in SQL.
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        $this->loadMissing(['order:id,order_number', 'participants.user:id,name']);

        return [
            'id' => (int) $this->id,
            'branch_id' => (int) $this->branch_id,
            'status' => $this->status->value,
            'order_number' => $this->order->order_number,
            'participant_names' => $this->participants
                ->map(fn (ConversationParticipant $participant): string => $participant->user->name)
                ->values()
                ->all(),
            'last_message_preview' => $this->last_message_preview,
            'last_message_at' => SearchableTimestamp::from($this->last_message_at),
        ];
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * @return HasMany<ConversationParticipant, $this>
     */
    public function participants(): HasMany
    {
        return $this->hasMany(ConversationParticipant::class);
    }

    /**
     * @return HasMany<Message, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function isOpen(): bool
    {
        return $this->status === ConversationStatus::Open;
    }

    public function isClosed(): bool
    {
        return $this->status === ConversationStatus::Closed;
    }

    /**
     * The Reverb private channel carrying this conversation's messages.
     */
    public function channelName(): string
    {
        return 'conversation.'.$this->id;
    }

    protected function casts(): array
    {
        return [
            'status' => ConversationStatus::class,
            'last_message_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }
}
