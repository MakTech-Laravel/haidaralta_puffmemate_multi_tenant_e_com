<?php

namespace App\DataTables;

use App\Enums\InventoryTransactionType;
use App\Enums\UserType;
use App\Models\DriverInventoryTransaction;
use App\Models\Product;
use App\Models\User;
use App\Support\MediaUrl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;

/**
 * @extends AbstractDataTable<DriverInventoryTransaction>
 */
class DriverInventoryTransactionDataTable extends AbstractDataTable
{
    public function __construct(
        Request $request,
        protected int $scopedBranchId,
        protected string $currentBranchSlug,
    ) {
        parent::__construct($request);
    }

    /**
     * @return array<string, mixed>
     */
    public static function makeFor(
        Request $request,
        int $scopedBranchId,
        string $currentBranchSlug,
    ): array {
        return static::instanceFor($request, $scopedBranchId, $currentBranchSlug)->toArray();
    }

    public static function instanceFor(
        Request $request,
        int $scopedBranchId,
        string $currentBranchSlug,
    ): static {
        return new static($request, $scopedBranchId, $currentBranchSlug);
    }

    /**
     * @return class-string<DriverInventoryTransaction>
     */
    protected function modelClass(): string
    {
        return DriverInventoryTransaction::class;
    }

    /**
     * @return Builder<DriverInventoryTransaction>
     */
    protected function baseQuery(): Builder
    {
        return DriverInventoryTransaction::query()
            ->where('branch_id', $this->scopedBranchId)
            ->with([
                'driver:id,name,phone,avatar',
                'product:id,name,slug,sku,image',
                'creator:id,name,user_type,branch_id',
            ]);
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
                        )
                        ->orWhereHas(
                            'driver',
                            fn (Builder $drivers): Builder => $drivers
                                ->where('name', 'like', '%'.$term.'%')
                                ->orWhere('phone', 'like', '%'.$term.'%'),
                        );
                });
            }),
            AllowedFilter::callback('driver_id', function (Builder $query, mixed $value): void {
                if ($value === null || $value === '' || $value === 'all') {
                    return;
                }

                $query->where('user_id', (int) $value);
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
        $driverId = $this->optionId($filters['driver_id'] ?? null);
        $productId = $this->optionId($filters['product_id'] ?? null);

        return [
            'selected_driver' => $this->selectedDriver($driverId),
            'selected_product' => $this->selectedProduct($productId),
            'types' => InventoryTransactionType::driverAssignOptions(),
        ];
    }

    /**
     * @param  DriverInventoryTransaction  $model
     * @return array<string, mixed>
     */
    protected function mapRow(Model $model): array
    {
        /** @var DriverInventoryTransaction $model */
        return [
            'id' => $model->id,
            'driver_id' => $model->user_id,
            'driver_name' => $model->driver?->name,
            'driver_phone' => $model->driver?->phone,
            'driver_avatar_url' => MediaUrl::resolve($model->driver?->avatar, fallback: null),
            'driver_url' => $this->driverUrl($model->driver),
            'product_id' => $model->product_id,
            'product_name' => $model->product?->name,
            'product_sku' => $model->product?->sku,
            'product_image_url' => $model->product?->imageUrl(),
            'product_url' => $this->productUrl($model->product),
            'type' => $model->type->value,
            'type_label' => $model->type->label(),
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
     * @return array{id: int, name: string, phone: ?string}|null
     */
    private function selectedDriver(?int $driverId): ?array
    {
        if ($driverId === null) {
            return null;
        }

        $driver = User::query()->find($driverId);

        if ($driver === null) {
            return null;
        }

        return [
            'id' => $driver->id,
            'name' => $driver->name,
            'phone' => $driver->phone,
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

    private function driverUrl(?User $driver): ?string
    {
        if ($driver === null) {
            return null;
        }

        return route('branch.drivers.show', [
            'branch' => $this->currentBranchSlug,
            'user' => $driver,
        ]);
    }

    private function productUrl(?Product $product): ?string
    {
        if ($product === null) {
            return null;
        }

        return route('branch.products.show', [
            'branch' => $this->currentBranchSlug,
            'product' => $product,
        ]);
    }

    private function creatorUrl(?User $creator): ?string
    {
        if (
            $creator === null
            || $creator->user_type !== UserType::BRANCH
            || $creator->branch_id !== $this->scopedBranchId
        ) {
            return null;
        }

        return route('branch.staff.show', [
            'branch' => $this->currentBranchSlug,
            'user' => $creator,
        ]);
    }
}
