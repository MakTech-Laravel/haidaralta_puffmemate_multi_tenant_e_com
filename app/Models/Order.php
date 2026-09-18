<?php

namespace App\Models;

use App\Contracts\DataTableConfigurable;
use App\Enums\OrderStatus;
use App\Models\Concerns\AuditsChanges;
use App\Models\Concerns\HasDataTableConfig;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property string $order_number
 * @property int $customer_id
 * @property int $branch_id
 * @property int|null $driver_id
 * @property OrderStatus $status
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
        'branch_id',
        'driver_id',
        'status',
    ];

    /**
     * @var list<string>
     */
    public static array $dataTableSearchable = ['order_number'];

    /**
     * @var list<string>
     */
    public static array $dataTableSortable = ['order_number', 'status', 'created_at'];

    /**
     * @var list<string>
     */
    public static array $dataTableFilterable = ['status', 'branch_id'];

    /**
     * @return BelongsTo<User, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    /**
     * @return HasOne<Conversation, $this>
     */
    public function conversation(): HasOne
    {
        return $this->hasOne(Conversation::class);
    }

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
        ];
    }
}
