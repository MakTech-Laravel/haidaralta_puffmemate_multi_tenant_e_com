<?php

namespace App\Models;

use App\Models\Concerns\AuditsChanges;
use App\Models\Concerns\HasDataTableConfig;
use App\Support\SearchableTimestamp;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Laravel\Scout\Searchable;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use AuditsChanges, HasDataTableConfig, HasFactory, Searchable, SoftDeletes;

    /**
     * @var list<string>
     */
    public static array $dataTableSearchable = ['name', 'slug', 'sku'];

    /**
     * @var list<string>
     */
    public static array $dataTableSortable = ['name', 'sku', 'status', 'created_at', 'variants_count'];

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
        'sku',
        'description',
        'image',
        'status',
        'created_by',
    ];

    /**
     * @return BelongsToMany<Category, $this>
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)->withTimestamps();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<ProductVariant, $this>
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * @return HasMany<ProductImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return HasMany<BranchProduct, $this>
     */
    public function branchProducts(): HasMany
    {
        return $this->hasMany(BranchProduct::class);
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
    public function branchPrices(): HasMany
    {
        return $this->hasMany(BranchProductPrice::class);
    }

    public function imageUrl(): ?string
    {
        return $this->image
            ? Storage::disk('public')->url($this->image)
            : null;
    }

    public function isEnabledForBranch(int $branchId): bool
    {
        return $this->branchProducts()
            ->where('branch_id', $branchId)
            ->where('status', true)
            ->exists();
    }

    public function categoryNamesLabel(): string
    {
        if (! $this->relationLoaded('categories')) {
            $this->load('categories:id,name');
        }

        $names = $this->categories->pluck('name')->filter()->values();

        return $names->isEmpty() ? '—' : $names->implode(', ');
    }

    /**
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        if (! $this->relationLoaded('categories')) {
            $this->load('categories:id');
        }

        return [
            'id' => (int) $this->id,
            'category_ids' => $this->categories->pluck('id')->map(fn ($id): int => (int) $id)->values()->all(),
            'name' => $this->name,
            'slug' => $this->slug,
            'sku' => $this->sku,
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
            'status' => 'boolean',
        ];
    }
}
