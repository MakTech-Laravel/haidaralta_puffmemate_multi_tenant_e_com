<?php

namespace App\Models;

use App\Contracts\DataTableConfigurable;
use App\Enums\WholesaleInquiryStatus;
use App\Models\Concerns\HasDataTableConfig;
use App\Services\Storefront\WholesaleInquiryService;
use Database\Factories\WholesaleInquiryFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $customer_id
 * @property int|null $order_id
 * @property string $full_name
 * @property string $phone
 * @property string|null $company
 * @property string|null $email
 * @property string $address
 * @property string|null $latitude
 * @property string|null $longitude
 * @property string|null $message
 * @property string|null $admin_note
 * @property string|null $rejection_reason
 * @property WholesaleInquiryStatus $status
 * @property Carbon|null $read_at
 * @property int|null $reviewed_by
 * @property Carbon|null $reviewed_at
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $customer
 * @property-read Order|null $order
 * @property-read User|null $reviewer
 * @property-read Collection<int, WholesaleInquiryItem> $items
 */
class WholesaleInquiry extends Model implements DataTableConfigurable
{
    /** @use HasFactory<WholesaleInquiryFactory> */
    use HasDataTableConfig, HasFactory;

    /**
     * @var list<string>
     */
    public static array $dataTableSearchable = ['full_name', 'company', 'email', 'phone'];

    /**
     * @var list<string>
     */
    public static array $dataTableSortable = ['full_name', 'status', 'created_at'];

    /**
     * @var list<string>
     */
    public static array $dataTableFilterable = ['status'];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'customer_id',
        'order_id',
        'full_name',
        'phone',
        'company',
        'email',
        'address',
        'latitude',
        'longitude',
        'message',
        'admin_note',
        'rejection_reason',
        'status',
        'read_at',
        'reviewed_by',
        'reviewed_at',
        'ip_address',
        'user_agent',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => WholesaleInquiryStatus::class,
            'read_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<WholesaleInquiryItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(WholesaleInquiryItem::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
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
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function markRead(): void
    {
        app(WholesaleInquiryService::class)->markRead($this);
    }
}
