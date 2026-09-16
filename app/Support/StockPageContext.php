<?php

namespace App\Support;

use App\Models\Branch;
use App\Models\BranchProductPrice;
use App\Models\Inventory;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

final class StockPageContext
{
    /**
     * @return array{
     *     locked: string|null,
     *     branch: array{id: int, name: string, slug: string}|null,
     *     product: array{id: int, name: string, slug: string, sku: ?string}|null,
     *     inventory: array{quantity: int}|null,
     *     price: array{selling_price: string|null, effective_price: string|null, has_discount: bool, discount_type: string, discount_price: string|null, start_date: ?string, end_date: ?string}|null,
     *     branch_options: list<array{id: int, name: string, slug: string}>,
     *     product_options: list<array{id: int, name: string, slug: string, sku: ?string}>
     * }
     */
    public static function fromRequest(Request $request, ?Branch $forcedBranch = null): array
    {
        $lockedRaw = $request->string('locked')->toString();
        $locked = in_array($lockedRaw, ['product', 'branch', 'both'], true) ? $lockedRaw : null;

        if ($forcedBranch !== null) {
            $locked = match ($locked) {
                'product', 'both' => 'both',
                default => 'branch',
            };
        }

        $branch = $forcedBranch ?? self::resolveBranch($request);
        $product = self::resolveProduct($request);

        if (in_array($locked, ['product', 'both'], true)) {
            abort_unless($product !== null, 404);
        }

        if (in_array($locked, ['branch', 'both'], true)) {
            abort_unless($branch !== null, 404);
        }

        $inventory = null;
        $price = null;

        if ($branch !== null && $product !== null) {
            $row = Inventory::query()
                ->where('branch_id', $branch->id)
                ->where('product_id', $product->id)
                ->whereNull('variant_id')
                ->first();

            $inventory = ['quantity' => $row?->quantity ?? 0];

            $priceRow = BranchProductPrice::query()
                ->where('branch_id', $branch->id)
                ->where('product_id', $product->id)
                ->whereNull('variant_id')
                ->where('status', true)
                ->first();

            $display = BranchProductPriceDisplay::listDisplay($priceRow);
            $price = [
                ...$display,
                'start_date' => $priceRow?->start_date?->toDateString(),
                'end_date' => $priceRow?->end_date?->toDateString(),
            ];
        }

        return [
            'locked' => $locked,
            'branch' => $branch === null ? null : [
                'id' => $branch->id,
                'name' => $branch->name,
                'slug' => $branch->slug,
            ],
            'product' => $product === null ? null : [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'sku' => $product->sku,
            ],
            'inventory' => $inventory,
            'price' => $price,
            'branch_options' => self::branchOptions(),
            'product_options' => self::productOptions(),
        ];
    }

    public static function requireBranch(Request $request, ?Branch $forcedBranch = null): Branch
    {
        $branch = $forcedBranch ?? self::resolveBranch($request, required: true);
        abort_unless($branch !== null, 404);

        return $branch;
    }

    public static function requireProduct(Request $request): Product
    {
        $product = self::resolveProduct($request, required: true);
        abort_unless($product !== null, 404);

        return $product;
    }

    /**
     * @return list<Product>
     */
    public static function requireProducts(mixed $ids): array
    {
        $productIds = collect(is_array($ids) ? $ids : [])
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        abort_unless($productIds->isNotEmpty(), 404);

        $products = Product::query()->whereIn('id', $productIds)->get();

        abort_unless($products->count() === $productIds->count(), 404);

        return $products->all();
    }

    /**
     * @return list<Branch>
     */
    public static function requireBranches(mixed $ids, ?Branch $forcedBranch = null): array
    {
        if ($forcedBranch !== null) {
            return [$forcedBranch];
        }

        $branchIds = collect(is_array($ids) ? $ids : [])
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        abort_unless($branchIds->isNotEmpty(), 404);

        $branches = Branch::query()->whereIn('id', $branchIds)->get();

        abort_unless($branches->count() === $branchIds->count(), 404);

        return $branches->all();
    }

    private static function resolveBranch(Request $request, bool $required = false): ?Branch
    {
        if (! $request->filled('branch') && ! $request->filled('branch_id')) {
            abort_if($required, 404);

            return null;
        }

        $value = $request->filled('branch')
            ? $request->string('branch')->toString()
            : $request->string('branch_id')->toString();

        $branch = ctype_digit($value)
            ? Branch::query()->find((int) $value)
            : Branch::query()->where('slug', $value)->first();

        abort_unless($branch !== null, 404);

        return $branch;
    }

    /**
     * @return array{
     *     data: list<array{id: int, name: string, slug: string}>,
     *     meta: array{current_page: int, last_page: int, per_page: int, total: int}
     * }
     */
    public static function paginatedBranchOptions(Request $request): array
    {
        $search = self::optionSearch($request);
        $perPage = self::optionPerPage($request);

        $query = Branch::query()->orderBy('name')->orderBy('id');

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('name', 'like', '%'.$search.'%')
                    ->orWhere('slug', 'like', '%'.$search.'%')
                    ->orWhere('code', 'like', '%'.$search.'%');
            });
        }

        $paginator = $query->paginate($perPage)->appends($request->query());

        return [
            'data' => collect($paginator->items())->map(fn (Branch $branch): array => [
                'id' => $branch->id,
                'name' => $branch->name,
                'slug' => $branch->slug,
            ])->values()->all(),
            'meta' => self::optionMeta($paginator),
        ];
    }

    /**
     * @return array{
     *     data: list<array{id: int, name: string, slug: string, sku: ?string}>,
     *     meta: array{current_page: int, last_page: int, per_page: int, total: int}
     * }
     */
    public static function paginatedProductOptions(Request $request): array
    {
        $search = self::optionSearch($request);
        $perPage = self::optionPerPage($request);

        $query = Product::query()->orderBy('name')->orderBy('id');

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('name', 'like', '%'.$search.'%')
                    ->orWhere('sku', 'like', '%'.$search.'%')
                    ->orWhere('slug', 'like', '%'.$search.'%');
            });
        }

        $paginator = $query->paginate($perPage)->appends($request->query());

        return [
            'data' => collect($paginator->items())->map(fn (Product $product): array => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'sku' => $product->sku,
            ])->values()->all(),
            'meta' => self::optionMeta($paginator),
        ];
    }

    private static function optionSearch(Request $request): string
    {
        return trim((string) (
            $request->input('filter.search')
            ?? $request->query('search', '')
        ));
    }

    private static function optionPerPage(Request $request): int
    {
        return min(50, max(10, (int) $request->integer('per_page', 20)));
    }

    /**
     * @param  LengthAwarePaginator<int, mixed>  $paginator
     * @return array{current_page: int, last_page: int, per_page: int, total: int}
     */
    private static function optionMeta(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }

    private static function resolveProduct(Request $request, bool $required = false): ?Product
    {
        if (! $request->filled('product') && ! $request->filled('product_id')) {
            abort_if($required, 404);

            return null;
        }

        $value = $request->filled('product')
            ? $request->string('product')->toString()
            : $request->string('product_id')->toString();

        $product = ctype_digit($value)
            ? Product::query()->find((int) $value)
            : Product::query()->where('slug', $value)->first();

        abort_unless($product !== null, 404);

        return $product;
    }

    /**
     * @return list<array{id: int, name: string, slug: string}>
     */
    private static function branchOptions(): array
    {
        return Branch::query()
            ->orderBy('name')
            ->get(['id', 'name', 'slug'])
            ->map(fn (Branch $branch): array => [
                'id' => $branch->id,
                'name' => $branch->name,
                'slug' => $branch->slug,
            ])
            ->all();
    }

    /**
     * @return list<array{id: int, name: string, slug: string, sku: ?string}>
     */
    private static function productOptions(): array
    {
        return Product::query()
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'sku'])
            ->map(fn (Product $product): array => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'sku' => $product->sku,
            ])
            ->all();
    }
}
