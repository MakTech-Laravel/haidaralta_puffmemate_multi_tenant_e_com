<?php

namespace App\Models;

use App\Enums\OrderReturnStatus;
use App\Enums\OrderReturnType;
use Database\Factories\OrderReturnFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Whole-order defect return or refund request.
 *
 * @property int $id
 * @property int $order_id
 * @property int $requester_id
 * @property OrderReturnType $type
 * @property string $reason
 * @property string|null $note
 * @property OrderReturnStatus $status
 * @property int|null $resolved_by
 */
class OrderReturn extends Model
{
    /** @use HasFactory<OrderReturnFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'order_id',
        'requester_id',
        'type',
        'reason',
        'note',
        'status',
        'resolved_by',
        'resolved_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => OrderReturnType::class,
            'status' => OrderReturnStatus::class,
            'resolved_at' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
