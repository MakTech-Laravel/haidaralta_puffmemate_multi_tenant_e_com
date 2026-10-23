<?php

namespace App\Http\Controllers\Branch;

use App\DataTables\BranchProductReviewDataTable;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use App\Support\Phone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductReviewController extends Controller
{
    public function index(Request $request, Branch $branch): Response
    {
        return Inertia::render('branch/reviews/index', [
            'branch' => $branch->toPortalContext(),
            'table' => BranchProductReviewDataTable::makeForBranch($request, $branch),
            'customerOptionsUrl' => route('branch.reviews.customer-options', $branch),
            'productOptionsUrl' => route('branch.reviews.product-options', $branch),
        ]);
    }

    public function show(Branch $branch, ProductReview $productReview): Response
    {
        $productReview = $this->resolveForBranch($branch, $productReview);
        $productReview->load([
            'user:id,name,phone,email',
            'product:id,name,sku,slug',
            'order:id,order_number',
            'order.fulfillment:id,order_id,branch_id',
            'order.fulfillment.branch:id,name,slug',
        ]);

        return Inertia::render('branch/reviews/show', [
            'branch' => $branch->toPortalContext(),
            'review' => $this->reviewPayload($branch, $productReview),
        ]);
    }

    public function hide(Branch $branch, ProductReview $productReview): RedirectResponse
    {
        $productReview = $this->resolveForBranch($branch, $productReview);

        if ($productReview->hidden_at === null) {
            $productReview->forceFill(['hidden_at' => now()])->save();
        }

        return back();
    }

    public function unhide(Branch $branch, ProductReview $productReview): RedirectResponse
    {
        $productReview = $this->resolveForBranch($branch, $productReview);

        if ($productReview->hidden_at !== null) {
            $productReview->forceFill(['hidden_at' => null])->save();
        }

        return back();
    }

    public function customerOptions(Request $request, Branch $branch): JsonResponse
    {
        $perPage = min(50, max(10, (int) $request->integer('per_page', 20)));
        $search = trim((string) $request->input('filter.search', ''));
        $branchId = $branch->id;

        $query = User::query()
            ->where('user_type', UserType::CUSTOMER)
            ->whereIn(
                'id',
                ProductReview::query()
                    ->select('user_id')
                    ->whereHas(
                        'order.fulfillment',
                        fn (Builder $fulfillments): Builder => $fulfillments->where('branch_id', $branchId),
                    ),
            )
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

    public function productOptions(Request $request, Branch $branch): JsonResponse
    {
        $perPage = min(50, max(10, (int) $request->integer('per_page', 20)));
        $search = trim((string) $request->input('filter.search', ''));
        $branchId = $branch->id;

        $query = Product::query()
            ->whereHas(
                'reviews.order.fulfillment',
                fn (Builder $fulfillments): Builder => $fulfillments->where('branch_id', $branchId),
            )
            ->orderBy('name')
            ->orderBy('id');

        if ($search !== '') {
            $query->where(function (Builder $filters) use ($search): void {
                $filters->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        $paginator = $query->paginate($perPage);

        return response()->json([
            'data' => $paginator->getCollection()->map(fn (Product $product): array => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
            ])->values()->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    private function resolveForBranch(Branch $branch, ProductReview $productReview): ProductReview
    {
        $productReview->loadMissing('order.fulfillment');

        abort_unless(
            $productReview->order?->fulfillment?->branch_id === $branch->id,
            404,
        );

        return $productReview;
    }

    /**
     * @return array{
     *     id: int,
     *     rating: int,
     *     body: string,
     *     hidden: bool,
     *     created_at: ?string,
     *     customer: array{id: ?int, name: ?string, phone: ?string, email: ?string, url: ?string},
     *     product: array{id: ?int, name: ?string, sku: ?string, slug: ?string, url: ?string},
     *     order: array{id: ?int, order_number: ?string, url: ?string},
     *     branch: ?array{id: int, name: string, slug: string}
     * }
     */
    private function reviewPayload(Branch $branch, ProductReview $review): array
    {
        $fulfillmentBranch = $review->order?->fulfillment?->branch;
        $product = $review->product;
        $order = $review->order;
        $customer = $review->user;
        $fulfillmentId = $review->order?->fulfillment?->id;

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
                'url' => $customer !== null
                    ? route('branch.customers.show', [$branch, $customer])
                    : null,
            ],
            'product' => [
                'id' => $product?->id,
                'name' => $product?->name,
                'sku' => $product?->sku,
                'slug' => $product?->slug,
                'url' => $product !== null
                    ? route('branch.products.show', [$branch, $product])
                    : null,
            ],
            'order' => [
                'id' => $order?->id,
                'order_number' => $order?->order_number,
                'url' => $fulfillmentId !== null
                    ? route('branch.orders.show', [$branch, $fulfillmentId])
                    : null,
            ],
            'branch' => $fulfillmentBranch === null ? null : [
                'id' => $fulfillmentBranch->id,
                'name' => $fulfillmentBranch->name,
                'slug' => $fulfillmentBranch->slug,
            ],
        ];
    }
}
