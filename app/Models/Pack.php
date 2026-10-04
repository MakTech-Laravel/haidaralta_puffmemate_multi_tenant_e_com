<?php

namespace App\Models;

use App\Contracts\DataTableConfigurable;
use App\Enums\PackOfferType;
use App\Models\Concerns\AuditsChanges;
use App\Models\Concerns\HasDataTableConfig;
use App\Models\Concerns\InteractsWithAppMedia;
use App\Support\CatalogMediaPayload;
use App\Support\MediaUrl;
use Database\Factories\PackFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;

/**
 * @property int $id
 * @property int $branch_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property PackOfferType $offer_type
 * @property string $offer_value
 * @property string $subtotal
 * @property string $pack_price
 * @property int $quantity
 * @property bool $status
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property Carbon|null $highlighted_at
 * @property-read Branch|null $branch
 * @property-read Collection<int, PackItem> $items
 * @property-read int $items_count
 */
class Pack extends Model implements DataTableConfigurable, HasMedia
{
    /** @use HasFactory<PackFactory> */
    use AuditsChanges, HasDataTableConfig, HasFactory, InteractsWithAppMedia;

    public function registerMediaCollections(): void
    {
        $this->registerSingleFileCollection('image');
        $this->registerMultiFileCollection('gallery');
    }

    /**
     * @var list<string>
     */
    public static array $dataTableSearchable = ['name', 'slug'];

    /**
     * @var list<string>
     */
    public static array $dataTableSortable = ['name', 'quantity', 'status', 'created_at'];

    /**
     * @var list<string>
     */
    public static array $dataTableFilterable = ['status'];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'branch_id',
        'name',
        'slug',
        'description',
        'offer_type',
        'offer_value',
        'subtotal',
        'pack_price',
        'quantity',
        'status',
        'starts_at',
        'ends_at',
        'highlighted_at',
    ];

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * @return HasMany<PackItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(PackItem::class)->orderBy('id');
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

    /**
     * @return array<string, mixed>
     */
    public function toPortal(bool $detailed = false): array
    {
        $payload = [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'image' => CatalogMediaPayload::fileName($this),
            'image_url' => $this->imageUrl(),
            'offer_type' => $this->offer_type->value,
            'offer_value' => $this->offer_value,
            'subtotal' => $this->subtotal,
            'pack_price' => $this->pack_price,
            'quantity' => $this->quantity,
            'status' => (bool) $this->status,
            'starts_at' => $this->starts_at?->toDateString(),
            'ends_at' => $this->ends_at?->toDateString(),
            'is_highlighted' => $this->highlighted_at !== null,
            'product_count' => (int) ($this->items_count ?? $this->items->count()),
        ];

        if (! $detailed) {
            return $payload;
        }

        $this->loadMissing(['items.product.media', 'media']);

        $payload['gallery'] = CatalogMediaPayload::gallery($this);

        $payload['items'] = $this->items
            ->map(function (PackItem $item): array {
                $product = $item->product;

                return [
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'product' => $product === null ? null : [
                        'id' => $product->id,
                        'name' => $product->name,
                        'sku' => $product->sku,
                        'image_url' => $product->imageUrl(),
                    ],
                ];
            })
            ->values()
            ->all();

        return $payload;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Branch routes bind by slug inside the branch; platform routes bind `{pack:id}` across branches.
     */
    public function resolveRouteBinding($value, $field = null): ?Model
    {
        if ($field === 'id') {
            return $this->whereKey($value)->first();
        }

        $branchId = $this->boundBranchId();

        if ($branchId === null) {
            return null;
        }

        return $this->where($field ?? $this->getRouteKeyName(), $value)
            ->where('branch_id', $branchId)
            ->first();
    }

    private function boundBranchId(): ?int
    {
        if (app()->bound('currentBranch')) {
            /** @var Branch $branch */
            $branch = app('currentBranch');

            return $branch->id;
        }

        $routeBranch = request()->route('branch');

        if ($routeBranch instanceof Branch) {
            return $routeBranch->id;
        }

        if (is_string($routeBranch) || is_numeric($routeBranch)) {
            $id = Branch::query()->where('slug', $routeBranch)->value('id');

            return $id === null ? null : (int) $id;
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'offer_type' => PackOfferType::class,
            'offer_value' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'pack_price' => 'decimal:2',
            'quantity' => 'integer',
            'status' => 'boolean',
            'starts_at' => 'date',
            'ends_at' => 'date',
            'highlighted_at' => 'datetime',
        ];
    }
}
