<?php

namespace App\Models;

use App\Models\Concerns\AuditsChanges;
use App\Models\Concerns\HasDataTableConfig;
use App\Support\SearchableTimestamp;
use Database\Factories\BranchFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;

class Branch extends Model
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
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'opening_time' => 'datetime:H:i',
            'closing_time' => 'datetime:H:i',
            'status' => 'boolean',
        ];
    }
}
