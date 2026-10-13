<?php

namespace App\Http\Controllers\Platform;

use App\Enums\DriverAssignmentStatus;
use App\Enums\DriverStockAssignmentMode;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\AssignDriverProductsRequest;
use App\Models\Branch;
use App\Models\Inventory;
use App\Models\User;
use App\Services\Driver\DriverStockAssignmentService;
use App\Support\MediaUrl;
use App\Support\Phone;
use App\Support\StockPageContext;
use App\Support\Toast;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class DriverProductAssignController extends Controller
{
    public function __construct(
        private DriverStockAssignmentService $assignments,
    ) {}

    public function create(Request $request): Response
    {
        $branch = StockPageContext::optionalBranch($request);
        $products = $branch === null ? [] : $this->productsForBranch($branch);

        return Inertia::render('platform/drivers/assign-products', [
            'branch' => $branch?->toPortalContext(),
            'branchOptionsUrl' => route('platform.drivers.branch-options'),
            'driverOptionsUrl' => $branch === null
                ? null
                : route('platform.assign-products.driver-options', ['branch' => $branch->id]),
            'products' => $products,
        ]);
    }

    public function driverOptions(Request $request): JsonResponse
    {
        $branch = $this->requireBranch($request);
        $perPage = min(50, max(10, (int) $request->integer('per_page', 20)));
        $search = trim((string) $request->input('filter.search', ''));

        $query = User::query()
            ->where('user_type', UserType::DRIVER)
            ->where('is_active', true)
            ->whereHas(
                'driverBranchAssignments',
                fn ($q) => $q
                    ->where('branch_id', $branch->id)
                    ->where('status', DriverAssignmentStatus::Active),
            )
            ->orderBy('name');

        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%");
                Phone::constrainLike($q, 'phone', $search);
            });
        }

        $paginator = $query->paginate($perPage);

        return response()->json([
            'data' => $paginator->getCollection()->map(fn (User $driver): array => [
                'id' => $driver->id,
                'name' => $driver->name,
                'sku' => $driver->phone,
            ])->values()->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function store(AssignDriverProductsRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $branch = Branch::query()->findOrFail((int) $data['branch_id']);
        $mode = DriverStockAssignmentMode::from($data['mode']);

        try {
            $this->assignments->assign(
                $branch,
                $mode,
                $data['driver_ids'],
                $data['items'],
                $request->user('platform'),
            );
        } catch (ValidationException $exception) {
            throw $exception;
        }

        Toast::success('Products assigned to driver stock.');

        return redirect()->route('platform.assign-products.create', [
            'branch' => $branch->id,
        ]);
    }

    /**
     * @return list<array{
     *     id: int,
     *     inventoryId: int,
     *     variantId: int|null,
     *     name: string,
     *     sku: string|null,
     *     image: string|null,
     *     available: int,
     *     priceLabel: string
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
            ->map(function (Inventory $inventory): ?array {
                $product = $inventory->product;

                if ($product === null) {
                    return null;
                }

                $available = max(0, $inventory->quantity - $inventory->reserved_quantity);

                if ($available < 1) {
                    return null;
                }

                return [
                    'id' => $product->id,
                    'inventoryId' => $inventory->id,
                    'variantId' => $inventory->variant_id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'image' => $product->imageUrl(MediaUrl::DEFAULT_FALLBACK),
                    'available' => $available,
                    'priceLabel' => '$'.number_format((float) $product->selling_price, 2),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function requireBranch(Request $request): Branch
    {
        $branch = StockPageContext::optionalBranch($request);

        abort_unless($branch !== null, 404);

        return $branch;
    }
}
