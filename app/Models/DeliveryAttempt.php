<?php

namespace App\Models;

use App\Enums\DeliveryFailureReason;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryAttempt extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'fulfillment_id',
        'driver_id',
        'reason',
        'note',
        'attempted_at',
    ];

    /**
     * @return BelongsTo<OrderFulfillment, $this>
     */
    public function fulfillment(): BelongsTo
    {
        return $this->belongsTo(OrderFulfillment::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reason' => DeliveryFailureReason::class,
            'attempted_at' => 'datetime',
        ];
    }
}
