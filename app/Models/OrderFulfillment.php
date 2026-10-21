<?php

namespace App\Models;

use App\Contracts\DataTableConfigurable;
use App\Enums\FulfillmentStatus;
use App\Models\Concerns\AuditsChanges;
use App\Models\Concerns\HasDataTableConfig;
use Database\Factories\OrderFulfillmentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Internal per-branch delivery unit for a master order.
 *
 * Never exposed to the customer guard — branch identity and shipping cost
 * splits are internal allocation detail only.
 *
 * @property int $id
 * @property int $order_id
 * @property int $branch_id
 * @property int|null $driver_id
 * @property string $fulfillment_number
 * @property FulfillmentStatus $status
 * @property string|null $distance_km
 * @property string $shipping_cost
 * @property Carbon|null $paid_at
 * @property string|null $notes
 * @property-read Order $order
 * @property-read Branch $branch
 * @property-read User|null $driver
 */
class OrderFulfillment extends Model implements DataTableConfigurable
{
    /** @use HasFactory<OrderFulfillmentFactory> */
    use AuditsChanges, HasDataTableConfig, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'order_id',
        'branch_id',
        'driver_id',
        'fulfillment_number',
        'status',
        'distance_km',
        'shipping_cost',
        'paid_at',
        'notes',
    ];

    /**
     * @var list<string>
     */
    public static array $dataTableSearchable = ['fulfillment_number'];

    /**
     * @var list<string>
     */
    public static array $dataTableSortable = ['fulfillment_number', 'status', 'shipping_cost', 'created_at'];

    /**
     * @var list<string>
     */
    public static array $dataTableFilterable = ['status', 'branch_id'];

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
     * Nullable until a same-branch driver is assigned.
     *
     * @return BelongsTo<User, $this>
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'fulfillment_id');
    }

    /**
     * @return HasMany<OrderStatusHistory, $this>
     */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class, 'fulfillment_id');
    }

    /**
     * @return HasMany<OrderDriverAssignmentHistory, $this>
     */
    public function driverAssignmentHistories(): HasMany
    {
        return $this->hasMany(OrderDriverAssignmentHistory::class, 'fulfillment_id');
    }

    /**
     * @return HasMany<StockTransfer, $this>
     */
    public function stockTransfers(): HasMany
    {
        return $this->hasMany(StockTransfer::class, 'fulfillment_id');
    }

    /**
     * @return HasMany<DeliveryAttempt, $this>
     */
    public function deliveryAttempts(): HasMany
    {
        return $this->hasMany(DeliveryAttempt::class, 'fulfillment_id');
    }

    public function isPaid(): bool
    {
        return $this->paid_at !== null;
    }

    /**
     * Finished drops visible on a driver's history: live assignee, or last
     * assignee after cancel/return cleared driver_id.
     *
     * @param  Builder<static>  $query
     * @param  list<string>  $historyStatuses
     * @return Builder<static>
     */
    public function scopeVisibleInDriverHistory(Builder $query, int $driverId, array $historyStatuses): Builder
    {
        return $query
            ->whereIn('status', $historyStatuses)
            ->where(function (Builder $ownership) use ($driverId): void {
                $ownership
                    ->where('driver_id', $driverId)
                    ->orWhere(function (Builder $finished) use ($driverId): void {
                        $finished
                            ->whereNull('driver_id')
                            ->whereExists(function ($sub) use ($driverId): void {
                                $sub->selectRaw('1')
                                    ->from('order_driver_assignment_histories as h')
                                    ->whereColumn('h.fulfillment_id', 'order_fulfillments.id')
                                    ->where('h.to_driver_id', $driverId)
                                    ->whereRaw(
                                        'h.id = (select max(id) from order_driver_assignment_histories where fulfillment_id = order_fulfillments.id)',
                                    );
                            });
                    });
            });
    }

    /**
     * Whether this driver was the most recent assignee before driver_id was cleared.
     */
    public function wasLastAssignedDriver(int $driverId): bool
    {
        $latest = $this->driverAssignmentHistories()
            ->latest('id')
            ->first();

        return $latest !== null && (int) $latest->to_driver_id === $driverId;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => FulfillmentStatus::class,
            'distance_km' => 'decimal:2',
            'shipping_cost' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }
}
