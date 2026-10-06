<?php

namespace App\Http\Controllers\Platform;

use App\DataTables\WholesaleInquiryDataTable;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Inventory;
use App\Models\WholesaleInquiry;
use App\Services\Storefront\WholesaleInquiryService;
use App\Support\BranchMap;
use App\Support\StockPageContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WholesaleInquiryController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('platform/wholesale-inquiries/index', [
            'table' => WholesaleInquiryDataTable::make($request),
        ]);
    }

    public function show(
        WholesaleInquiry $wholesaleInquiry,
        WholesaleInquiryService $wholesaleInquiries,
    ): Response {
        $wholesaleInquiries->markRead($wholesaleInquiry);

        return Inertia::render('platform/wholesale-inquiries/show', [
            'inquiry' => $this->inquiryPayload($wholesaleInquiry),
        ]);
    }

    public function createPlaceOrder(Request $request): Response
    {
        return $this->renderPlaceOrder($request, null);
    }

    public function placeOrder(Request $request, WholesaleInquiry $wholesaleInquiry): Response
    {
        return $this->renderPlaceOrder($request, $wholesaleInquiry);
    }

    private function renderPlaceOrder(Request $request, ?WholesaleInquiry $inquiry): Response
    {
        $branch = StockPageContext::optionalBranch($request);

        return Inertia::render('platform/wholesale-inquiries/place-order', [
            'inquiry' => $inquiry === null ? null : $this->inquiryPayload($inquiry),
            'branch' => $branch?->toPortalContext(),
            'branchOptionsUrl' => route('platform.wholesale-inquiries.branch-options'),
            'products' => $branch === null ? [] : $this->productsForBranch($branch),
        ]);
    }

    public function branchOptions(Request $request): JsonResponse
    {
        $search = trim((string) (
            $request->input('filter.search')
            ?? $request->query('search', '')
        ));
        $perPage = min(50, max(10, (int) $request->integer('per_page', 20)));
        $status = $request->input('filter.status', $request->query('status', '1'));

        $query = Branch::query()->orderBy('name');

        if ($status === '1' || $status === 'true' || $status === true) {
            $query->where('status', true);
        } elseif ($status === '0' || $status === 'false' || $status === false) {
            $query->where('status', false);
        }

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $paginator = $query->paginate($perPage)->appends($request->query());

        return response()->json([
            'data' => collect($paginator->items())->map(fn (Branch $branch): array => [
                'id' => $branch->id,
                'name' => $branch->name,
                'slug' => $branch->slug,
                'code' => $branch->code,
                'status' => (bool) $branch->status,
            ])->values()->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * @return array{
     *     id: int,
     *     full_name: string,
     *     phone: string,
     *     company: ?string,
     *     email: ?string,
     *     address: string,
     *     latitude: ?string,
     *     longitude: ?string,
     *     map: ?array{google: string, open_street_map: string, embed: string},
     *     message: string,
     *     status: string,
     *     status_label: string,
     *     read_at: ?string,
     *     ip_address: ?string,
     *     user_agent: ?string,
     *     created_at: ?string
     * }
     */
    private function inquiryPayload(WholesaleInquiry $inquiry): array
    {
        return [
            'id' => $inquiry->id,
            'full_name' => $inquiry->full_name,
            'phone' => $inquiry->phone,
            'company' => $inquiry->company,
            'email' => $inquiry->email,
            'address' => $inquiry->address,
            'latitude' => $inquiry->latitude,
            'longitude' => $inquiry->longitude,
            'map' => BranchMap::links($inquiry->latitude, $inquiry->longitude),
            'message' => $inquiry->message,
            'status' => $inquiry->status->value,
            'status_label' => $inquiry->status->label(),
            'read_at' => $inquiry->read_at?->toIso8601String(),
            'ip_address' => $inquiry->ip_address,
            'user_agent' => $inquiry->user_agent,
            'created_at' => $inquiry->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return list<array{
     *     id: string,
     *     branch_id: int,
     *     name: string,
     *     sku: string,
     *     price: float,
     *     stock: int,
     *     image: ?string
     * }>
     */
    private function productsForBranch(Branch $branch): array
    {
        return Inventory::query()
            ->where('branch_id', $branch->id)
            ->where('status', true)
            ->where('quantity', '>', 0)
            ->with(['product:id,name,sku,selling_price,status', 'product.media'])
            ->whereHas('product', fn ($query) => $query->where('status', true))
            ->orderBy('product_id')
            ->get()
            ->map(function (Inventory $inventory) use ($branch): ?array {
                $product = $inventory->product;

                if ($product === null) {
                    return null;
                }

                $available = max(0, $inventory->quantity - $inventory->reserved_quantity);

                if ($available < 1) {
                    return null;
                }

                return [
                    'id' => (string) $product->id,
                    'branch_id' => $branch->id,
                    'name' => $product->name,
                    'sku' => (string) ($product->sku ?? ''),
                    'price' => (float) $product->selling_price,
                    'stock' => $available,
                    'image' => $product->imageUrl(),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }
}
