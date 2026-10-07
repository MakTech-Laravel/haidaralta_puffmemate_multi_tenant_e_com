<?php

namespace App\Http\Controllers\Branch;

use App\Enums\DriverAssignmentStatus;
use App\Enums\DriverStockAssignmentMode;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Branch\AssignDriverProductsRequest;
use App\Models\Branch;
use App\Models\Inventory;
use App\Models\User;
use App\Services\Driver\DriverStockAssignmentService;
use App\Support\MediaUrl;
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

    public function create(Branch $branch): Response
    {
        $products = Inventory::query()
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

        return Inertia::render('branch/drivers/assign-products', [
            'branch' => $branch->toPortalContext(),
            'driverOptionsUrl' => route('branch.assign-products.driver-options', $branch),
            'products' => $products,
        ]);
    }

    public function driverOptions(Request $request, Branch $branch): JsonResponse
    {
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
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
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

    public function store(AssignDriverProductsRequest $request, Branch $branch): RedirectResponse
    {
        $data = $request->validated();
        $mode = DriverStockAssignmentMode::from($data['mode']);

        try {
            $this->assignments->assign(
                $branch,
                $mode,
                $data['driver_ids'],
                $data['items'],
                $request->user('branch'),
            );
        } catch (ValidationException $exception) {
            throw $exception;
        }

        Toast::success('Products assigned to driver stock.');

        return redirect()->route('branch.assign-products.create', $branch);
    }
}
