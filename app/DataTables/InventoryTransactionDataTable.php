<?php

namespace App\DataTables;

use App\Enums\InventoryTransactionType;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;

class InventoryTransactionDataTable extends AbstractDataTable
{
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
        return (new static($request, $scopedBranchId, $portal, $currentBranchSlug))->toArray();
    }

    /**
     * @return class-string<InventoryTransaction>
     */
    protected function modelClass(): string
    {
        return InventoryTransaction::class;
    }

    /**
     * @return Builder<InventoryTransaction>
     */
    protected function baseQuery(): Builder
    {
        $query = InventoryTransaction::query()
            ->with([
                'branch:id,name,slug',
                'product:id,name,slug,sku',
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
            'type',
            'quantity',
            'created_at',
            'before_quantity',
            'after_quantity',
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
                    $builder->where('type', 'like', '%'.$term.'%')
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
            AllowedFilter::callback('type', function (Builder $query, mixed $value): void {
                if ($value === null || $value === '' || $value === 'all') {
                    return;
                }

                $query->where('type', (string) $value);
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
            'types' => InventoryTransactionType::manualOptions(),
        ];
    }

    /**
     * @param  InventoryTransaction  $model
     * @return array<string, mixed>
     */
    protected function mapRow(Model $model): array
    {
        /** @var InventoryTransaction $model */
        return [
            'id' => $model->id,
            'branch_id' => $model->branch_id,
            'branch_name' => $model->branch?->name,
            'branch_url' => $this->branchUrl($model->branch),
            'product_id' => $model->product_id,
            'product_name' => $model->product?->name,
            'product_sku' => $model->product?->sku,
            'product_url' => $this->productUrl($model->product),
            'type' => $model->type->value,
            'type_label' => $model->type->label(),
            'reason' => $model->reason?->value,
            'reason_note' => $model->reason_note,
            'reason_label' => $model->reasonLabel(),
            'quantity' => $model->quantity,
            'before_quantity' => $model->before_quantity,
            'after_quantity' => $model->after_quantity,
            'created_by_name' => $model->creator?->name,
            'created_by_url' => $this->creatorUrl($model->creator),
            'created_at' => $model->created_at?->toIso8601String(),
        ];
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
                UserType::PLATFORM => route('platform.staff.show', $creator),
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
