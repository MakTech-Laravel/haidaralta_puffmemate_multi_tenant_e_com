<?php

namespace App\Http\Controllers\Platform;

use App\DataTables\WholesaleInquiryDataTable;
use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\RejectWholesaleInquiryRequest;
use App\Http\Requests\Platform\UpdateWholesaleInquiryNoteRequest;
use App\Models\Branch;
use App\Models\BranchProductPrice;
use App\Models\DeliveryTimeSlot;
use App\Models\Inventory;
use App\Models\PaymentGatewaySetting;
use App\Models\User;
use App\Models\WholesaleInquiry;
use App\Models\WholesaleInquiryItem;
use App\Services\Application\PaymentGatewaySettingsService;
use App\Services\Storefront\WholesaleInquiryService;
use App\Support\BranchMap;
use App\Support\BranchProductPriceDisplay;
use App\Support\StockPageContext;
use App\Support\Toast;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WholesaleInquiryController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $admin */
        $admin = $request->user('platform');

        return Inertia::render('platform/wholesale-inquiries/index', [
            'table' => WholesaleInquiryDataTable::make($request),
            'canViewOrders' => $admin->can(PermissionEnum::MANAGE_ORDERS->value),
        ]);
    }

    public function show(
        Request $request,
        WholesaleInquiry $wholesaleInquiry,
        WholesaleInquiryService $wholesaleInquiries,
    ): Response {
        $wholesaleInquiries->markRead($wholesaleInquiry);

        /** @var User $admin */
        $admin = $request->user('platform');

        return Inertia::render('platform/wholesale-inquiries/show', [
            'inquiry' => $this->inquiryPayload($wholesaleInquiry),
            'canViewOrders' => $admin->can(PermissionEnum::MANAGE_ORDERS->value),
            'canViewCustomers' => $admin->can(PermissionEnum::MANAGE_USERS->value),
        ]);
    }

    public function reject(
        RejectWholesaleInquiryRequest $request,
        WholesaleInquiry $wholesaleInquiry,
        WholesaleInquiryService $wholesaleInquiries,
    ): RedirectResponse {
        /** @var User $admin */
        $admin = $request->user('platform');

        $wholesaleInquiries->reject($wholesaleInquiry, $admin, (string) $request->validated('reason'));

        Toast::success('Inquiry #'.$wholesaleInquiry->id.' rejected.');

        return redirect()->route('platform.wholesale-inquiries.show', $wholesaleInquiry);
    }

    public function updateNote(
        UpdateWholesaleInquiryNoteRequest $request,
        WholesaleInquiry $wholesaleInquiry,
        WholesaleInquiryService $wholesaleInquiries,
    ): RedirectResponse {
        $wholesaleInquiries->updateNote($wholesaleInquiry, $request->validated('admin_note'));

        Toast::success('Note saved.');

        return back();
    }

    public function createPlaceOrder(Request $request): Response
    {
        return $this->renderPlaceOrder($request, null);
    }

    public function placeOrder(Request $request, WholesaleInquiry $wholesaleInquiry): Response|RedirectResponse
    {
        if (! $wholesaleInquiry->status->isOpen()) {
            Toast::error('Inquiry #'.$wholesaleInquiry->id.' is already '.strtolower($wholesaleInquiry->status->label()).'.');

            return redirect()->route('platform.wholesale-inquiries.show', $wholesaleInquiry);
        }

        return $this->renderPlaceOrder($request, $wholesaleInquiry);
    }

    private function renderPlaceOrder(Request $request, ?WholesaleInquiry $inquiry): Response
    {
        $branch = StockPageContext::optionalBranch($request);

        return Inertia::render('platform/wholesale-inquiries/place-order', [
            'inquiry' => $inquiry === null ? null : $this->inquiryPayload($inquiry),
            'branch' => $branch?->toPortalContext(),
            'branchOptionsUrl' => route('platform.wholesale-inquiries.branch-options'),
            'deliveryQuoteUrl' => route('platform.wholesale-orders.delivery-quote'),
            'products' => $branch === null ? [] : $this->productsForBranch($branch),
            'paymentMethods' => $this->paymentMethods(),
            'timeSlots' => $this->timeSlots(),
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
     * @return array<string, mixed>
     */
    private function inquiryPayload(WholesaleInquiry $inquiry): array
    {
        $inquiry->loadMissing(['items.product', 'customer:id,name,phone', 'order:id,order_number', 'reviewer:id,name']);

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
            'is_open' => $inquiry->status->isOpen(),
            'read_at' => $inquiry->read_at?->toIso8601String(),
            'ip_address' => $inquiry->ip_address,
            'user_agent' => $inquiry->user_agent,
            'created_at' => $inquiry->created_at?->toIso8601String(),
            'admin_note' => $inquiry->admin_note,
            'rejection_reason' => $inquiry->rejection_reason,
            'reviewed_at' => $inquiry->reviewed_at?->toIso8601String(),
            'reviewer' => $inquiry->reviewer?->name,
            'customer' => $inquiry->customer === null ? null : [
                'id' => $inquiry->customer->id,
                'name' => $inquiry->customer->name,
                'phone' => $inquiry->customer->phone,
            ],
            'order' => $inquiry->order === null ? null : [
                'id' => $inquiry->order->id,
                'order_number' => $inquiry->order->order_number,
            ],
            'items' => $inquiry->items
                ->map(function (WholesaleInquiryItem $item): array {
                    $wholesale = $item->product === null
                        ? ['amount' => null, 'min_qty' => null]
                        : BranchProductPriceDisplay::wholesaleForProductContext($item->product, null);

                    return [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'variant_id' => $item->variant_id,
                        'name' => $item->product_name_snapshot,
                        'sku' => $item->sku_snapshot,
                        'quantity' => $item->quantity,
                        'unit_price' => (float) ($wholesale['amount'] ?? 0),
                        'min_quantity' => $wholesale['min_qty'],
                    ];
                })
                ->values()
                ->all(),
        ];
    }

    /**
     * One row per stocked product or product variant, keyed like the driver catalog
     * (`{product}` or `{product}:{variant}`). Variants use their own branch price row when one
     * exists, otherwise the product's.
     *
     * @return list<array{
     *     id: string,
     *     product_id: int,
     *     variant_id: int|null,
     *     branch_id: int,
     *     name: string,
     *     sku: string,
     *     price: float,
     *     min_quantity: int|null,
     *     is_wholesale_price: bool,
     *     stock: int,
     *     image: ?string
     * }>
     */
    private function productsForBranch(Branch $branch): array
    {
        $inventories = Inventory::query()
            ->where('branch_id', $branch->id)
            ->where('status', true)
            ->where('quantity', '>', 0)
            ->with(['product', 'product.media', 'variant:id,product_id,name,sku,status'])
            ->whereHas('product', fn ($query) => $query->where('status', true))
            ->orderBy('product_id')
            ->orderBy('variant_id')
            ->get();

        $branchPrices = BranchProductPriceDisplay::usesBranchPricing()
            ? BranchProductPrice::query()
                ->where('branch_id', $branch->id)
                ->whereIn('product_id', $inventories->pluck('product_id')->unique())
                ->where('status', true)
                ->get()
                ->keyBy(fn (BranchProductPrice $price): string => $price->product_id.':'.($price->variant_id ?? ''))
            : collect();

        return $inventories
            ->map(function (Inventory $inventory) use ($branch, $branchPrices): ?array {
                $product = $inventory->product;

                if ($product === null) {
                    return null;
                }

                $variant = $inventory->variant;

                if ($inventory->variant_id !== null && ($variant === null || ! $variant->status)) {
                    return null;
                }

                $available = max(0, $inventory->quantity - $inventory->reserved_quantity);

                if ($available < 1) {
                    return null;
                }

                $wholesale = BranchProductPriceDisplay::wholesaleForProductContext(
                    $product,
                    $branchPrices->get($product->id.':'.($variant?->id ?? ''))
                        ?? $branchPrices->get($product->id.':'),
                );

                return [
                    'id' => $variant === null ? (string) $product->id : $product->id.':'.$variant->id,
                    'product_id' => $product->id,
                    'variant_id' => $variant?->id,
                    'branch_id' => $branch->id,
                    'name' => $variant === null ? $product->name : $product->name.' · '.$variant->name,
                    'sku' => (string) ($variant?->sku ?: $product->sku ?? ''),
                    'price' => (float) ($wholesale['amount'] ?? 0),
                    'min_quantity' => $wholesale['min_qty'],
                    'is_wholesale_price' => $wholesale['is_wholesale'],
                    'stock' => $available,
                    'image' => $product->imageUrl(),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return list<array{id: string, label: string, description?: string|null}>
     */
    private function paymentMethods(): array
    {
        return collect(app(PaymentGatewaySettingsService::class)->enabledMethods())
            ->filter(fn (array $method): bool => in_array($method['id'] ?? null, PaymentGatewaySetting::KEYS, true))
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    private function timeSlots(): array
    {
        return DeliveryTimeSlot::query()
            ->where('status', true)
            ->whereHas('deliveryMethod', fn ($query) => $query->where('status', true))
            ->orderBy('sort')
            ->orderBy('id')
            ->pluck('label')
            ->unique()
            ->values()
            ->all();
    }
}
