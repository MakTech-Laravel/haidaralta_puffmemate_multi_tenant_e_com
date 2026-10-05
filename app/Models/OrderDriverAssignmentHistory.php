<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderDriverAssignmentHistory extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'order_id',
        'fulfillment_id',
        'from_driver_id',
        'to_driver_id',
        'reason',
        'changed_by',
    ];

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<OrderFulfillment, $this>
     */
    public function fulfillment(): BelongsTo
    {
        return $this->belongsTo(OrderFulfillment::class, 'fulfillment_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function fromDriver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_driver_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function toDriver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_driver_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function changer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
