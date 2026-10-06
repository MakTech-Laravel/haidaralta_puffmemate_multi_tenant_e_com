<?php

namespace App\Http\Controllers\Platform;

use App\DataTables\ProductReviewDataTable;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Models\ProductReview;
use App\Models\User;
use App\Support\StockPageContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductReviewController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('platform/reviews/index', [
            'table' => ProductReviewDataTable::make($request),
            'customerOptionsUrl' => route('platform.reviews.customer-options'),
            'productOptionsUrl' => route('platform.reviews.product-options'),
            'branchOptionsUrl' => route('platform.reviews.branch-options'),
        ]);
    }

    public function hide(ProductReview $productReview): RedirectResponse
    {
        if ($productReview->hidden_at === null) {
            $productReview->forceFill(['hidden_at' => now()])->save();
        }

        return back();
    }

    public function unhide(ProductReview $productReview): RedirectResponse
    {
        if ($productReview->hidden_at !== null) {
            $productReview->forceFill(['hidden_at' => null])->save();
        }

        return back();
    }

    public function customerOptions(Request $request): JsonResponse
    {
        $perPage = min(50, max(10, (int) $request->integer('per_page', 20)));
        $search = trim((string) $request->input('filter.search', ''));

        $query = User::query()
            ->where('user_type', UserType::CUSTOMER)
            ->orderBy('name')
            ->orderBy('id');

        if ($search !== '') {
            $query->where(function ($filters) use ($search): void {
                $filters->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $paginator = $query->paginate($perPage);

        return response()->json([
            'data' => $paginator->getCollection()->map(fn (User $customer): array => [
                'id' => $customer->id,
                'name' => $customer->name,
                'sku' => $customer->phone ?: $customer->email,
            ])->values()->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function productOptions(Request $request): JsonResponse
    {
        return response()->json(StockPageContext::paginatedProductOptions($request));
    }

    public function branchOptions(Request $request): JsonResponse
    {
        return response()->json(StockPageContext::paginatedBranchOptions($request));
    }
}
