<?php

namespace App\DataTables;

use App\Enums\PriceChangeSource;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\BranchProductPriceHistory;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;

class BranchProductPriceHistoryDataTable extends AbstractDataTable
{
    /**
     * @var array<string, string>
     */
    private const CHANGE_LABELS = [
        'buying_price' => 'Buying',
        'regular_price' => 'Regular',
        'selling_price' => 'Selling',
        'discount_type' => 'Discount',
        'discount_price' => 'Discount value',
        'start_date' => 'Start date',
        'end_date' => 'End date',
    ];

    public function __construct(
        Request $request,
        protected ?int $scopedBranchId = null,
        protected string $portal = 'platform',
        protected ?string $currentBranchSlug = null,
    ) {
        parent::__construct($request);
    }

    /**
     * @return array<string, mixed>
     */
    public static function makeFor(
        Request $request,
        ?int $scopedBranchId = null,
        string $portal = 'platform',
        ?string $currentBranchSlug = null,
    ): array {
        return static::instanceFor($request, $scopedBranchId, $portal, $currentBranchSlug)->toArray();
    }

    public static function instanceFor(
        Request $request,
        ?int $scopedBranchId = null,
        string $portal = 'platform',
        ?string $currentBranchSlug = null,
    ): static {
        return new static($request, $scopedBranchId, $portal, $currentBranchSlug);
    }

    public function includesBranchColumn(): bool
    {
        return $this->scopedBranchId === null;
    }

    /**
     * @param  array<string, mixed>  $changes
     */
    public static function flattenChanges(array $changes): string
    {
        return collect(self::mapChangeRows($changes))
            ->map(fn (array $row): string => sprintf('%s: %s → %s', $row['label'], $row['from'], $row['to']))
            ->implode('; ');
    }

    /**
     * @return class-string<BranchProductPriceHistory>
     */
    protected function modelClass(): string
    {
        return BranchProductPriceHistory::class;
    }

    /**
     * @return Builder<BranchProductPriceHistory>
     */
    protected function baseQuery(): Builder
    {
        $query = BranchProductPriceHistory::query()
            ->with([
                'branch:id,name,slug',
                'product:id,name,slug,sku',
                'variant:id,name,sku',
                'creator:id,name,user_type,branch_id',
            ]);

        if ($this->scopedBranchId !== null) {
            $query->where('branch_id', $this->scopedBranchId);
        }

        return $query;
    }

    /**
     * @return list<string>
     */
    protected function allowedSorts(): array
    {
        return [
            'source',
            'created_at',
        ];
    }

    protected function defaultSort(): string
    {
        return '-created_at';
    }

    /**
     * @return list<AllowedFilter|string>
     */
    protected function allowedFilters(): array
    {
        return [
            AllowedFilter::callback('search', function (Builder $query, mixed $value): void {
                $term = trim((string) $value);

                if ($term === '') {
                    return;
                }

                $query->where(function (Builder $builder) use ($term): void {
                    $builder->where('source', 'like', '%'.$term.'%')
                        ->orWhereHas(
                            'product',
                            fn (Builder $products): Builder => $products
                                ->where('name', 'like', '%'.$term.'%')
                                ->orWhere('sku', 'like', '%'.$term.'%')
                                ->orWhere('slug', 'like', '%'.$term.'%'),
                        );

                    if ($this->scopedBranchId === null) {
                        $builder->orWhereHas(
                            'branch',
                            fn (Builder $branches): Builder => $branches
                                ->where('name', 'like', '%'.$term.'%')
                                ->orWhere('slug', 'like', '%'.$term.'%'),
                        );
                    }
                });
            }),
            AllowedFilter::callback('branch_id', function (Builder $query, mixed $value): void {
                if ($this->scopedBranchId !== null || $value === null || $value === '' || $value === 'all') {
                    return;
                }

                $query->where('branch_id', (int) $value);
            }),
            AllowedFilter::callback('product_id', function (Builder $query, mixed $value): void {
                if ($value === null || $value === '' || $value === 'all') {
                    return;
                }

                $query->where('product_id', (int) $value);
            }),
            AllowedFilter::callback('source', function (Builder $query, mixed $value): void {
                if ($value === null || $value === '' || $value === 'all') {
                    return;
                }

                $query->where('source', (string) $value);
            }),
            AllowedFilter::callback('from', function (Builder $query, mixed $value): void {
                $from = trim((string) $value);

                if ($from === '') {
                    return;
                }

                $query->whereDate('created_at', '>=', $from);
            }),
            AllowedFilter::callback('to', function (Builder $query, mixed $value): void {
                $to = trim((string) $value);

                if ($to === '') {
                    return;
                }

                $query->whereDate('created_at', '<=', $to);
            }),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function filterOptions(): array
    {
        $filters = $this->normalizedFilters();
        $branchId = $this->optionId($filters['branch_id'] ?? null);
        $productId = $this->optionId($filters['product_id'] ?? null);

        return [
            'selected_branch' => $this->scopedBranchId !== null
                ? null
                : $this->selectedBranch($branchId),
            'selected_product' => $this->selectedProduct($productId),
            'sources' => collect(PriceChangeSource::cases())
                ->map(fn (PriceChangeSource $source): array => [
                    'value' => $source->value,
                    'label' => $source->label(),
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  BranchProductPriceHistory  $model
     * @return array<string, mixed>
     */
    protected function mapRow(Model $model): array
    {
        /** @var BranchProductPriceHistory $model */
        return [
            'id' => $model->id,
            'branch_id' => $model->branch_id,
            'branch_name' => $model->branch?->name,
            'branch_url' => $this->branchUrl($model->branch),
            'product_id' => $model->product_id,
            'product_name' => $model->product?->name,
            'product_sku' => $model->product?->sku,
            'product_url' => $this->productUrl($model->product),
            'variant_id' => $model->variant_id,
            'variant_name' => $model->variant?->name,
            'source' => $model->source->value,
            'source_label' => $model->source->label(),
            'changes' => $this->mapChanges($model->changes ?? []),
            'created_by_name' => $model->creator?->name,
            'created_by_url' => $this->creatorUrl($model->creator),
            'created_at' => $model->created_at?->toIso8601String(),
        ];
    }

    /**
     * @param  array<string, mixed>  $changes
     * @return list<array{field: string, label: string, from: string, to: string}>
     */
    private function mapChanges(array $changes): array
    {
        return self::mapChangeRows($changes);
    }

    /**
     * @param  array<string, mixed>  $changes
     * @return list<array{field: string, label: string, from: string, to: string}>
     */
    private static function mapChangeRows(array $changes): array
    {
        $rows = [];

        foreach ($changes as $field => $change) {
            if (! is_array($change)) {
                continue;
            }

            $from = $change['from'] ?? null;
            $to = $change['to'] ?? null;

            $rows[] = [
                'field' => (string) $field,
                'label' => self::CHANGE_LABELS[$field] ?? (string) $field,
                'from' => self::formatChangeValue((string) $field, $from),
                'to' => self::formatChangeValue((string) $field, $to),
            ];
        }

        return $rows;
    }

    private static function formatChangeValue(string $field, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if (in_array($field, ['buying_price', 'regular_price', 'selling_price', 'discount_price'], true)) {
            return number_format((float) $value, 2, '.', '');
        }

        if ($field === 'discount_type') {
            return match ((string) $value) {
                'none' => 'None',
                'fixed' => 'Amount off',
                'percent' => 'Percent',
                default => (string) $value,
            };
        }

        return (string) $value;
    }

    private function optionId(mixed $value): ?int
    {
        if ($value === null || $value === '' || $value === 'all') {
            return null;
        }

        $id = (int) $value;

        return $id > 0 ? $id : null;
    }

    /**
     * @return array{id: int, name: string, slug: string}|null
     */
    private function selectedBranch(?int $branchId): ?array
    {
        if ($branchId === null) {
            return null;
        }

        $branch = Branch::query()->find($branchId);

        if ($branch === null) {
            return null;
        }

        return [
            'id' => $branch->id,
            'name' => $branch->name,
            'slug' => $branch->slug,
        ];
    }

    /**
     * @return array{id: int, name: string, sku: ?string}|null
     */
    private function selectedProduct(?int $productId): ?array
    {
        if ($productId === null) {
            return null;
        }

        $product = Product::query()->find($productId);

        if ($product === null) {
            return null;
        }

        return [
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
        ];
    }

    private function branchUrl(?Branch $branch): ?string
    {
        if ($branch === null || $this->portal !== 'platform') {
            return null;
        }

        return route('platform.branches.show', $branch);
    }

    private function productUrl(?Product $product): ?string
    {
        if ($product === null) {
            return null;
        }

        if ($this->portal === 'branch' && $this->currentBranchSlug !== null) {
            return route('branch.products.show', [
                'branch' => $this->currentBranchSlug,
                'product' => $product,
            ]);
        }

        return route('platform.products.show', $product);
    }

    private function creatorUrl(?User $creator): ?string
    {
        if ($creator === null) {
            return null;
        }

        if ($this->portal === 'platform') {
            return match ($creator->user_type) {
                UserType::PLATFORM => route('platform.admins.show', $creator),
                UserType::BRANCH => route('platform.branch-staff.show', $creator),
                default => null,
            };
        }

        if (
            $creator->user_type === UserType::BRANCH
            && $this->currentBranchSlug !== null
            && $creator->branch_id === $this->scopedBranchId
        ) {
            return route('branch.staff.show', [
                'branch' => $this->currentBranchSlug,
                'user' => $creator,
            ]);
        }

        return null;
    }
}
