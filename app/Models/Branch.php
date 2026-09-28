<?php

namespace App\Models;

use App\Contracts\DataTableConfigurable;
use App\Contracts\ScoutSearchable;
use App\Models\Concerns\AuditsChanges;
use App\Models\Concerns\HasDataTableConfig;
use App\Support\MediaUrl;
use App\Support\SearchableTimestamp;
use Database\Factories\BranchFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Laravel\Scout\Searchable;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $code
 * @property string|null $logo
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $address
 * @property string|null $latitude
 * @property string|null $longitude
 * @property string|null $delivery_radius_km
 * @property Carbon|null $opening_time
 * @property Carbon|null $closing_time
 * @property bool $status
 * @property-read Collection<int, User> $users
 * @property-read Collection<int, Inventory> $inventories
 * @property-read Collection<int, BranchProductPrice> $productPrices
 * @property-read Collection<int, DriverBranchAssignment> $driverBranchAssignments
 * @property-read Collection<int, DriverRoster> $driverRosters
 * @property-read Collection<int, Conversation> $conversations
 * @property-read Collection<int, OrderFulfillment> $fulfillments
 * @property-read BranchShippingRule|null $shippingRule
 */
class Branch extends Model implements DataTableConfigurable, ScoutSearchable
{
    /** @use HasFactory<BranchFactory> */
    use AuditsChanges, HasDataTableConfig, HasFactory, Searchable;

    /**
     * @var list<string>
     */
    public static array $dataTableSearchable = ['name', 'slug', 'code'];

    /**
     * @var list<string>
     */
    public static array $dataTableSortable = ['name', 'slug', 'code', 'status', 'created_at', 'users_count'];

    /**
     * @var list<string>
     */
    public static array $dataTableFilterable = ['status'];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'code',
        'logo',
        'phone',
        'email',
        'address',
        'latitude',
        'longitude',
        'delivery_radius_km',
        'opening_time',
        'closing_time',
        'status',
    ];

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return HasMany<Inventory, $this>
     */
    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    /**
     * @return HasMany<Pack, $this>
     */
    public function packs(): HasMany
    {
        return $this->hasMany(Pack::class);
    }

    /**
     * @return HasMany<BranchProductPrice, $this>
     */
    public function productPrices(): HasMany
    {
        return $this->hasMany(BranchProductPrice::class);
    }

    /**
     * @return HasMany<DriverBranchAssignment, $this>
     */
    public function driverBranchAssignments(): HasMany
    {
        return $this->hasMany(DriverBranchAssignment::class);
    }

    /**
     * @return HasMany<DriverRoster, $this>
     */
    public function driverRosters(): HasMany
    {
        return $this->hasMany(DriverRoster::class);
    }

    /**
     * @return HasMany<Conversation, $this>
     */
    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    /**
     * @return HasMany<OrderFulfillment, $this>
     */
    public function fulfillments(): HasMany
    {
        return $this->hasMany(OrderFulfillment::class);
    }

    /**
     * @return HasOne<BranchShippingRule, $this>
     */
    public function shippingRule(): HasOne
    {
        return $this->hasOne(BranchShippingRule::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => (int) $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'code' => $this->code,
            'address' => $this->address,
            'status' => (bool) $this->status,
            'created_at' => SearchableTimestamp::from($this->created_at),
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Shared Inertia branch portal context (sidebar mark + chrome).
     *
     * @return array{id: int, name: string, slug: string, logo_url: string|null}
     */
    public function toPortalContext(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'logo_url' => MediaUrl::resolve($this->logo, fallback: null),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'delivery_radius_km' => 'decimal:2',
            'opening_time' => 'datetime:H:i',
            'closing_time' => 'datetime:H:i',
            'status' => 'boolean',
        ];
    }
}
