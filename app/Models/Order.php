<?php

namespace App\Models;

use App\Contracts\DataTableConfigurable;
use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Concerns\AuditsChanges;
use App\Models\Concerns\HasDataTableConfig;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Customer-facing master order.
 *
 * Never carries branch/fulfillment identity directly — that detail lives on
 * {@see OrderFulfillment} and must never be serialized to the customer guard.
 *
 * @property int $id
 * @property string $order_number
 * @property int $customer_id
 * @property int|null $placed_by
 * @property OrderSource $source
 * @property string|null $idempotency_key
 * @property OrderStatus $status
 * @property PaymentStatus $payment_status
 * @property int|null $pack_id
 * @property int|null $pack_quantity
 */
class Order extends Model implements DataTableConfigurable
{
    /** @use HasFactory<OrderFactory> */
    use AuditsChanges, HasDataTableConfig, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'order_number',
        'customer_id',
        'placed_by',
        'source',
        'idempotency_key',
        'address_id',
        'coupon_id',
        'coupon_code',
        'status',
        'shipping_address_snapshot',
        'latitude',
        'longitude',
        'subtotal',
        'discount',
        'delivery_fee',
        'tax',
        'total_amount',
        'payment_status',
        'payment_method',
        'delivery_method',
        'delivery_date',
        'time_slot',
        'notes',
        'accepts_late_delivery',
        'pack_id',
        'pack_quantity',
    ];

    /**
     * @var list<string>
     */
    public static array $dataTableSearchable = ['order_number'];

    /**
     * @var list<string>
     */
    public static array $dataTableSortable = ['order_number', 'status', 'total_amount', 'created_at'];

    /**
     * @var list<string>
     */
    public static array $dataTableFilterable = ['status', 'payment_status', 'source'];

    /**
     * @return BelongsTo<User, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /**
     * Ops actor who created the order. Null for storefront checkout.
     *
     * @return BelongsTo<User, $this>
     */
    public function placedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'placed_by');
    }

    /**
     * @return HasOne<Conversation, $this>
     */
    public function conversation(): HasOne
    {
        return $this->hasOne(Conversation::class);
    }

    /**
     * @return BelongsTo<Address, $this>
     */
    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    /**
     * @return BelongsTo<Coupon, $this>
     */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    /**
     * Packs & Saves buy-now orders only. Null for normal product cart checkout.
     *
     * @return BelongsTo<Pack, $this>
     */
    public function pack(): BelongsTo
    {
        return $this->belongsTo(Pack::class);
    }

    public function isPackCheckout(): bool
    {
        return $this->pack_id !== null;
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @return HasMany<OrderReturn, $this>
     */
    public function returns(): HasMany
    {
        return $this->hasMany(OrderReturn::class);
    }

    /**
     * The single fulfillment for this order.
     *
     * @return HasOne<OrderFulfillment, $this>
     */
    public function fulfillment(): HasOne
    {
        return $this->hasOne(OrderFulfillment::class);
    }

    /**
     * @return HasMany<OrderFulfillment, $this>
     */
    public function fulfillments(): HasMany
    {
        return $this->hasMany(OrderFulfillment::class);
    }

    /**
     * @return HasOne<OrderFulfillment, $this>
     */
    public function primaryFulfillment(): HasOne
    {
        return $this->hasOne(OrderFulfillment::class);
    }

    /**
     * @return HasMany<StockTransfer, $this>
     */
    public function stockTransfers(): HasMany
    {
        return $this->hasMany(StockTransfer::class);
    }

    /**
     * Every status change for this order, including fulfillment transitions.
     *
     * @return HasMany<OrderStatusHistory, $this>
     */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    /**
     * @return HasOne<Payment, $this>
     */
    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'source' => OrderSource::class,
            'payment_status' => PaymentStatus::class,
            'shipping_address_snapshot' => 'array',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'delivery_fee' => 'decimal:2',
            'tax' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'delivery_date' => 'date',
            'pack_quantity' => 'integer',
            'accepts_late_delivery' => 'boolean',
        ];
    }
}
