<?php

namespace App\Http\Controllers\Platform;

use App\DataTables\ProductReviewDataTable;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Models\ProductReview;
use App\Models\User;
use App\Support\Phone;
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

    public function show(ProductReview $productReview): Response
    {
        $productReview->load([
            'user:id,name,phone,email',
            'product:id,name,sku,slug',
            'order:id,order_number',
            'order.fulfillment:id,order_id,branch_id',
            'order.fulfillment.branch:id,name,slug',
        ]);

        return Inertia::render('platform/reviews/show', [
            'review' => $this->reviewPayload($productReview),
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

    /**
     * @return array{
     *     id: int,
     *     rating: int,
     *     body: string,
     *     hidden: bool,
     *     created_at: ?string,
     *     customer: array{id: ?int, name: ?string, phone: ?string, email: ?string},
     *     product: array{id: ?int, name: ?string, sku: ?string, slug: ?string, url: ?string},
     *     order: array{id: ?int, order_number: ?string, url: ?string},
     *     branch: ?array{id: int, name: string, slug: string}
     * }
     */
    private function reviewPayload(ProductReview $review): array
    {
        $branch = $review->order?->fulfillment?->branch;
        $product = $review->product;
        $order = $review->order;
        $customer = $review->user;

        return [
            'id' => $review->id,
            'rating' => $review->rating,
            'body' => $review->body,
            'hidden' => $review->isHidden(),
            'created_at' => $review->created_at?->toIso8601String(),
            'customer' => [
                'id' => $customer?->id,
                'name' => $customer?->name,
                'phone' => $customer?->phone,
                'email' => $customer?->email,
            ],
            'product' => [
                'id' => $product?->id,
                'name' => $product?->name,
                'sku' => $product?->sku,
                'slug' => $product?->slug,
                'url' => $product !== null
                    ? route('platform.products.show', $product)
                    : null,
            ],
            'order' => [
                'id' => $order?->id,
                'order_number' => $order?->order_number,
                'url' => $order !== null
                    ? route('platform.orders.show', $order)
                    : null,
            ],
            'branch' => $branch === null ? null : [
                'id' => $branch->id,
                'name' => $branch->name,
                'slug' => $branch->slug,
            ],
        ];
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
                    ->orWhere('email', 'like', "%{$search}%");
                Phone::constrainLike($filters, 'phone', $search);
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
