<?php

namespace App\Models;

use App\Contracts\DataTableConfigurable;
use App\Models\Concerns\HasDataTableConfig;
use Database\Factories\BranchAreaFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $branch_id
 * @property string $name
 * @property string $address
 * @property string $latitude
 * @property string $longitude
 * @property string $city
 * @property string $state
 * @property string|null $postal_code
 * @property string|null $notes
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Branch $branch
 * @property-read Collection<int, BranchAreaTransferHistory> $transferHistories
 */
class BranchArea extends Model implements DataTableConfigurable
{
    /** @use HasFactory<BranchAreaFactory> */
    use HasDataTableConfig, HasFactory;

    /**
     * @var list<string>
     */
    public static array $dataTableSearchable = ['name', 'address', 'city', 'state', 'postal_code'];

    /**
     * @var list<string>
     */
    public static array $dataTableSortable = ['name', 'city', 'state', 'is_active', 'created_at'];

    /**
     * @var list<string>
     */
    public static array $dataTableFilterable = ['is_active'];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'branch_id',
        'name',
        'address',
        'latitude',
        'longitude',
        'city',
        'state',
        'postal_code',
        'notes',
        'is_active',
    ];

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * @return HasMany<BranchAreaTransferHistory, $this>
     */
    public function transferHistories(): HasMany
    {
        return $this->hasMany(BranchAreaTransferHistory::class)->orderByDesc('effective_date')->orderByDesc('id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'is_active' => 'boolean',
        ];
    }
}
