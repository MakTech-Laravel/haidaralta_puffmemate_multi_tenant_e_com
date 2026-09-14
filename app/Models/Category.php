<?php

namespace App\Models;

use App\Models\Concerns\AuditsChanges;
use App\Models\Concerns\HasDataTableConfig;
use App\Support\CatalogBackgroundColor;
use App\Support\MediaUrl;
use App\Support\SearchableTimestamp;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use AuditsChanges, HasDataTableConfig, HasFactory, Searchable, SoftDeletes;

    /**
     * @var list<string>
     */
    public static array $dataTableSearchable = ['name', 'slug'];

    /**
     * @var list<string>
     */
    public static array $dataTableSortable = ['name', 'slug', 'status', 'featured', 'created_at', 'products_count'];

    /**
     * @var list<string>
     */
    public static array $dataTableFilterable = ['parent_id', 'status', 'featured'];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'image',
        'color_code',
        'status',
        'featured',
    ];

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
            'parent_id' => $this->parent_id,
            'status' => (bool) $this->status,
            'featured' => (bool) $this->featured,
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
            'color_code' => 'array',
        ];
    }

    public function backgroundStyle(): ?string
    {
        return CatalogBackgroundColor::cssBackground($this->color_code);
    }
}
