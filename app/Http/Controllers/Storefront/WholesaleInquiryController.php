<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\StoreWholesaleInquiryRequest;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\Storefront\WholesaleInquiryService;
use App\Support\BranchProductPriceDisplay;
use App\Support\Toast;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WholesaleInquiryController extends Controller
{
    public function store(
        StoreWholesaleInquiryRequest $request,
        WholesaleInquiryService $wholesaleInquiries,
    ): RedirectResponse {
        $customer = $request->user('customer');

        $wholesaleInquiries->store(
            $request->validated(),
            $request->ip(),
            $request->userAgent(),
            $customer instanceof User ? $customer : null,
        );

        Toast::success('Thanks — your wholesale inquiry was sent.');

        return back();
    }

    public function products(Request $request): JsonResponse
    {
        $search = trim((string) (
            $request->input('filter.search')
            ?? $request->query('search', '')
        ));
        $perPage = min(50, max(10, (int) $request->integer('per_page', 20)));

        $query = Product::query()
            ->where('status', true)
            ->with([
                'media',
                'variants' => fn ($variants) => $variants
                    ->where('status', true)
                    ->orderBy('name')
                    ->select(['id', 'product_id', 'name', 'sku']),
            ])
            ->orderBy('name')
            ->orderBy('id');

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('name', 'like', '%'.$search.'%')
                    ->orWhere('sku', 'like', '%'.$search.'%');
            });
        }

        $paginator = $query->paginate($perPage)->appends($request->query());

        return response()->json([
            'data' => collect($paginator->items())->map(function (Product $product): array {
                $wholesale = BranchProductPriceDisplay::wholesaleForProductContext($product, null);

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'sku' => $product->sku,
                    'image' => $product->imageUrl(),
                    'pack_label' => $product->pack_label,
                    'min_wholesale_qty' => $wholesale['min_qty'],
                    'wholesale_price' => $wholesale['is_wholesale'] ? $wholesale['amount'] : null,
                    'variants' => $product->variants
                        ->map(fn (ProductVariant $variant): array => [
                            'id' => $variant->id,
                            'name' => $variant->name,
                            'sku' => $variant->sku,
                        ])
                        ->values()
                        ->all(),
                ];
            })->values()->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }
}
