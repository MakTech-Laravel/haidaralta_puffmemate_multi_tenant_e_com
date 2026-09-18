<?php

namespace App\Models;

use App\Contracts\DataTableConfigurable;
use App\Contracts\ScoutSearchable;
use App\Models\Concerns\AuditsChanges;
use App\Models\Concerns\HasDataTableConfig;
use App\Support\CatalogBackgroundColor;
use App\Support\MediaUrl;
use App\Support\SearchableTimestamp;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $sku
 * @property string|null $description
 * @property string|null $brand
 * @property string|null $image
 * @property array<mixed>|null $tags
 * @property array<string, mixed>|null $specifications
 * @property array<string, mixed>|null $color_code
 * @property bool $status
 * @property int|null $created_by
 * @property-read Collection<int, Category> $categories
 * @property-read User|null $creator
 * @property-read Collection<int, ProductVariant> $variants
 * @property-read Collection<int, ProductImage> $images
 * @property-read Collection<int, Inventory> $inventories
 * @property-read Collection<int, BranchProductPrice> $branchPrices
 */
class Product extends Model implements DataTableConfigurable, ScoutSearchable
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
        'brand',
        'image',
        'tags',
        'specifications',
        'color_code',
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
        return MediaUrl::resolve($this->image, fallback: null);
    }

    public static function skuFromSlug(string $slug): string
    {
        $prefix = strtoupper(trim((string) config('catalog.sku_prefix', 'PMM')));
        $body = strtoupper(trim($slug, '-'));

        if ($body === '') {
            return $prefix;
        }

        return $prefix.'-'.$body;
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
            'brand' => $this->brand,
            'tags' => $this->tags ?? [],
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
            'tags' => 'array',
            'specifications' => 'array',
            'color_code' => 'array',
        ];
    }

    public function backgroundStyle(): ?string
    {
        return CatalogBackgroundColor::cssBackground($this->color_code);
    }
}
