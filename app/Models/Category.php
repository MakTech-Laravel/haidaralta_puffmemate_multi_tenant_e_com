<?php

namespace App\Models;

use App\Contracts\DataTableConfigurable;
use App\Contracts\ScoutSearchable;
use App\Models\Concerns\AuditsChanges;
use App\Models\Concerns\HasDataTableConfig;
use App\Support\CatalogBackgroundColor;
use App\Support\MediaUrl;
use App\Support\SearchableTimestamp;
use App\Support\Storefront\StorefrontCatalog;
use Database\Factories\CategoryFactory;
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
 * @property int|null $parent_id
 * @property string $name
 * @property string $slug
 * @property string|null $image
 * @property string|null $slogan
 * @property array<string, mixed>|null $color_code
 * @property bool $status
 * @property bool $featured
 * @property bool $show_in_nav
 * @property int|null $nav_sort
 * @property-read Category|null $parent
 * @property-read Collection<int, Category> $children
 * @property-read Collection<int, Product> $products
 */
class Category extends Model implements DataTableConfigurable, ScoutSearchable
{
    /** @use HasFactory<CategoryFactory> */
    use AuditsChanges, HasDataTableConfig, HasFactory, Searchable, SoftDeletes;

    /**
     * @var list<string>
     */
    public static array $dataTableSearchable = ['name', 'slug', 'slogan'];

    /**
     * @var list<string>
     */
    public static array $dataTableSortable = [
        'name',
        'slug',
        'status',
        'featured',
        'show_in_nav',
        'nav_sort',
        'created_at',
        'products_count',
    ];

    /**
     * @var list<string>
     */
    public static array $dataTableFilterable = ['parent_id', 'status', 'featured', 'show_in_nav'];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'slogan',
        'image',
        'color_code',
        'status',
        'featured',
        'show_in_nav',
        'nav_sort',
    ];

    protected static function booted(): void
    {
        $forgetNav = static function (): void {
            StorefrontCatalog::forgetNavCategories();
        };

        static::saved($forgetNav);
        static::deleted($forgetNav);
        static::restored($forgetNav);
        static::forceDeleted($forgetNav);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<Category, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * @return BelongsToMany<Product, $this>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)->withTimestamps();
    }

    public function imageUrl(): ?string
    {
        return MediaUrl::resolve($this->image, fallback: null);
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
            'slogan' => $this->slogan,
            'parent_id' => $this->parent_id,
            'status' => (bool) $this->status,
            'featured' => (bool) $this->featured,
            'show_in_nav' => (bool) $this->show_in_nav,
            'nav_sort' => $this->nav_sort,
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
            'featured' => 'boolean',
            'show_in_nav' => 'boolean',
            'nav_sort' => 'integer',
            'color_code' => 'array',
        ];
    }

    public function backgroundStyle(): ?string
    {
        return CatalogBackgroundColor::cssBackground($this->color_code);
    }
}
