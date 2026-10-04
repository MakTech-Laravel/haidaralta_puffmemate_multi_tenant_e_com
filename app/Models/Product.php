<?php

namespace App\Models;

use App\Contracts\DataTableConfigurable;
use App\Contracts\ScoutSearchable;
use App\Enums\DiscountType;
use App\Models\Concerns\AuditsChanges;
use App\Models\Concerns\HasDataTableConfig;
use App\Models\Concerns\InteractsWithAppMedia;
use App\Support\BranchProductPriceDisplay;
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
use Illuminate\Support\Carbon;
use Laravel\Scout\Searchable;
use Spatie\MediaLibrary\HasMedia;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $sku
 * @property string|null $description
 * @property string|null $brand
 * @property string|null $pack_label
 * @property int|null $pieces_per_pack
 * @property string|null $pack_unit
 * @property array<mixed>|null $tags
 * @property array<string, mixed>|null $specifications
 * @property array<string, mixed>|null $color_code
 * @property string|null $selling_price
 * @property string|null $regular_price
 * @property DiscountType $discount_type
 * @property string|null $discount_price
 * @property Carbon|null $discount_start_date
 * @property Carbon|null $discount_end_date
 * @property bool $status
 * @property int $views
 * @property int|null $created_by
 * @property-read Collection<int, Category> $categories
 * @property-read User|null $creator
 * @property-read Collection<int, ProductVariant> $variants
 * @property-read Collection<int, Inventory> $inventories
 * @property-read Collection<int, BranchProductPrice> $branchPrices
 * @property-read Collection<int, ProductReview> $reviews
 */
class Product extends Model implements DataTableConfigurable, HasMedia, ScoutSearchable
{
    /** @use HasFactory<ProductFactory> */
    use AuditsChanges, HasDataTableConfig, HasFactory, InteractsWithAppMedia, Searchable, SoftDeletes;

    public function registerMediaCollections(): void
    {
        $this->registerSingleFileCollection('image');
        $this->registerMultiFileCollection('gallery');
    }

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
        'pack_label',
        'pieces_per_pack',
        'pack_unit',
        'tags',
        'specifications',
        'color_code',
        'selling_price',
        'regular_price',
        'discount_type',
        'discount_price',
        'discount_start_date',
        'discount_end_date',
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

    /**
     * @return HasMany<ProductReview, $this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }

    public function imageUrl(?string $fallback = null): ?string
    {
        return MediaUrl::fromCollection($this, 'image', $fallback);
    }

    /**
     * @return list<string>
     */
    public function galleryUrls(): array
    {
        return $this->mediaUrlsFor('gallery');
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
     * Storefront/admin card line, e.g. "Gold Pack • 20 Cigarettes" or "Refillable Lighter".
     */
    public function packSummary(): ?string
    {
        $label = is_string($this->pack_label) ? trim($this->pack_label) : '';
        $pieces = $this->pieces_per_pack;
        $unit = is_string($this->pack_unit) ? trim($this->pack_unit) : '';

        if ($pieces !== null && $pieces > 0) {
            $unitPart = $unit !== '' ? $unit : 'pcs';
            $countPart = $pieces.' '.$unitPart;

            return $label !== '' ? $label.' • '.$countPart : $countPart;
        }

        return $label !== '' ? $label : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        if (! $this->relationLoaded('categories')) {
            $this->load('categories:id');
        }

        $sellingPrice = $this->selling_price === null ? null : (float) $this->selling_price;
        $effectivePrice = $sellingPrice === null
            ? null
            : BranchProductPriceDisplay::catalogEffectiveAmount($this);

        return [
            'id' => (int) $this->id,
            'category_ids' => $this->categories->pluck('id')->map(fn ($id): int => (int) $id)->values()->all(),
            'name' => $this->name,
            'slug' => $this->slug,
            'sku' => $this->sku,
            'brand' => $this->brand,
            'description' => str(strip_tags((string) $this->description))->limit(5000)->toString(),
            'pack_label' => $this->pack_label,
            'tags' => $this->tags ?? [],
            'status' => (bool) $this->status,
            'selling_price' => $sellingPrice,
            'effective_price' => $effectivePrice,
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
            'views' => 'integer',
            'tags' => 'array',
            'specifications' => 'array',
            'color_code' => 'array',
            'pieces_per_pack' => 'integer',
            'selling_price' => 'decimal:2',
            'regular_price' => 'decimal:2',
            'discount_type' => DiscountType::class,
            'discount_price' => 'decimal:2',
            'discount_start_date' => 'date',
            'discount_end_date' => 'date',
        ];
    }

    public function backgroundStyle(): ?string
    {
        return CatalogBackgroundColor::cssBackground($this->color_code);
    }
}
