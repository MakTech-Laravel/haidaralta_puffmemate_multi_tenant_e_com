<?php

namespace App\DataTables;

use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\QueryBuilder\AllowedFilter;

/**
 * @extends AbstractDataTable<ProductReview>
 */
class ProductReviewDataTable extends AbstractDataTable
{
    /**
     * @return class-string<ProductReview>
     */
    protected function modelClass(): string
    {
        return ProductReview::class;
    }

    /**
     * @return Builder<ProductReview>
     */
    protected function baseQuery(): Builder
    {
        return ProductReview::query()
            ->with([
                'user:id,name,phone,email',
                'product:id,name,sku,slug',
                'order:id,order_number',
                'order.fulfillment:id,order_id,branch_id',
                'order.fulfillment.branch:id,name,slug',
            ]);
    }

    /**
     * @return list<string>
     */
    protected function allowedSorts(): array
    {
        return ['created_at', 'id'];
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
                    $builder->where('body', 'like', '%'.$term.'%')
                        ->orWhereHas(
                            'user',
                            fn (Builder $users): Builder => $users
                                ->where('name', 'like', '%'.$term.'%')
                                ->orWhere('phone', 'like', '%'.$term.'%')
                                ->orWhere('email', 'like', '%'.$term.'%'),
                        )
                        ->orWhereHas(
                            'product',
                            fn (Builder $products): Builder => $products
                                ->where('name', 'like', '%'.$term.'%')
                                ->orWhere('sku', 'like', '%'.$term.'%'),
                        )
                        ->orWhereHas(
                            'order',
                            fn (Builder $orders): Builder => $orders
                                ->where('order_number', 'like', '%'.$term.'%'),
                        )
                        ->orWhereHas(
                            'order.fulfillment.branch',
                            fn (Builder $branches): Builder => $branches
                                ->where('name', 'like', '%'.$term.'%'),
                        );
                });
            }),
            AllowedFilter::callback('user_id', function (Builder $query, mixed $value): void {
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
            AllowedFilter::callback('branch_id', function (Builder $query, mixed $value): void {
                if ($value === null || $value === '' || $value === 'all') {
                    return;
                }

                $query->whereHas(
                    'order.fulfillment',
                    fn (Builder $fulfillments): Builder => $fulfillments
                        ->where('branch_id', (int) $value),
                );
            }),
            AllowedFilter::callback('order_number', function (Builder $query, mixed $value): void {
                $term = trim((string) $value);

                if ($term === '') {
                    return;
                }

                $query->whereHas(
                    'order',
                    fn (Builder $orders): Builder => $orders
                        ->where('order_number', 'like', '%'.$term.'%'),
                );
            }),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function filterOptions(): array
    {
        $filters = $this->normalizedFilters();
        $userId = isset($filters['user_id']) ? (int) $filters['user_id'] : 0;
        $productId = isset($filters['product_id']) ? (int) $filters['product_id'] : 0;
        $branchId = isset($filters['branch_id']) ? (int) $filters['branch_id'] : 0;

        $selectedCustomer = null;

        if ($userId > 0) {
            $customer = User::query()->whereKey($userId)->first(['id', 'name', 'phone', 'email']);

            if ($customer !== null) {
                $selectedCustomer = [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'phone' => $customer->phone,
                    'email' => $customer->email,
                ];
            }
        }

        $selectedProduct = null;

        if ($productId > 0) {
            $product = Product::query()->whereKey($productId)->first(['id', 'name', 'sku', 'slug']);

            if ($product !== null) {
                $selectedProduct = [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'slug' => $product->slug,
                ];
            }
        }

        $selectedBranch = null;

        if ($branchId > 0) {
            $branch = Branch::query()->whereKey($branchId)->first(['id', 'name', 'slug']);

            if ($branch !== null) {
                $selectedBranch = [
                    'id' => $branch->id,
                    'name' => $branch->name,
                    'slug' => $branch->slug,
                ];
            }
        }

        return [
            'selected_customer' => $selectedCustomer,
            'selected_product' => $selectedProduct,
            'selected_branch' => $selectedBranch,
        ];
    }

    /**
     * @param  ProductReview  $model
     * @return array<string, mixed>
     */
    protected function mapRow(Model $model): array
    {
        $branch = $model->order?->fulfillment?->branch;

        return [
            'id' => $model->id,
            'customer_name' => $model->user?->name,
            'product_name' => $model->product?->name,
            'order_number' => $model->order?->order_number,
            'branch_name' => $branch?->name,
            'body' => $model->body,
            'rating' => $model->rating,
            'hidden' => $model->isHidden(),
            'created_at' => $model->created_at?->toIso8601String(),
        ];
    }
}
