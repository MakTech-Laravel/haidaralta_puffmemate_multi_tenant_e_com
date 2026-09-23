<?php

namespace App\Http\Controllers\Platform;

use App\DataTables\DriverDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\StoreDriverRequest;
use App\Http\Requests\Platform\SyncDriverBranchesRequest;
use App\Http\Requests\Platform\UpdateDriverRequest;
use App\Models\Branch;
use App\Models\User;
use App\Services\Driver\DriverService;
use App\Services\Workforce\TransferEmployeeService;
use App\Support\DriverPayload;
use App\Support\Toast;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class DriverController extends Controller
{
    public function __construct(
        private DriverService $drivers,
        private TransferEmployeeService $transfers,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('platform/drivers/index', [
            'table' => DriverDataTable::make($request),
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

    public function create(): Response
    {
        return Inertia::render('platform/drivers/create', [
            'branches' => DriverPayload::branchOptions(),
            'vehicleTypes' => DriverPayload::vehicleTypeOptions(),
            'availabilityStatuses' => DriverPayload::availabilityOptions(),
        ]);
    }

    public function store(StoreDriverRequest $request): RedirectResponse
    {
        $data = $request->validated();

        try {
            $driver = $this->drivers->create(
                $data,
                assignedBy: $request->user('platform'),
                avatar: $request->file('avatar'),
                vehicleImage: $request->file('vehicle_image'),
            );
        } catch (InvalidArgumentException $exception) {
            Toast::error($exception->getMessage());

            return redirect()->back();
        }

        Toast::success('Driver created.');

        return redirect()->route('platform.drivers.show', $driver);
    }

    public function show(User $user): Response
    {
        $driver = DriverPayload::resolveDriver($user);

        return Inertia::render('platform/drivers/show', [
            'driver' => DriverPayload::detail($driver),
            'branches' => DriverPayload::branchOptions(),
            'assignmentStatuses' => DriverPayload::assignmentStatusOptions(),
        ]);
    }

    public function edit(User $user): Response
    {
        $driver = DriverPayload::resolveDriver($user);
        $driver->loadMissing('driverProfile');

        return Inertia::render('platform/drivers/edit', [
            'driver' => DriverPayload::detail($driver),
            'branches' => DriverPayload::branchOptions(),
            'vehicleTypes' => DriverPayload::vehicleTypeOptions(),
            'availabilityStatuses' => DriverPayload::availabilityOptions(),
        ]);
    }

    public function update(UpdateDriverRequest $request, User $user): RedirectResponse
    {
        $driver = DriverPayload::resolveDriver($user);
        $data = $request->validated();

        try {
            if (array_key_exists('branch_ids', $data)) {
                $this->transfers->syncDriverBranches(
                    $driver,
                    array_map('intval', $data['branch_ids'] ?? []),
                    $request->user('platform'),
                );
            }

            $this->drivers->update(
                $driver,
                $data,
                avatar: $request->file('avatar'),
                vehicleImage: $request->file('vehicle_image'),
            );
        } catch (InvalidArgumentException $exception) {
            Toast::error($exception->getMessage());

            return redirect()->back();
        }

        Toast::success('Driver updated.');

        return redirect()->route('platform.drivers.show', $driver);
    }

    public function destroy(User $user): RedirectResponse
    {
        $driver = DriverPayload::resolveDriver($user);
        $driver->update(['is_active' => false]);

        Toast::success('Driver deactivated.');

        return redirect()->route('platform.drivers.index');
    }

    public function syncBranches(SyncDriverBranchesRequest $request, User $user): RedirectResponse
    {
        $driver = DriverPayload::resolveDriver($user);

        try {
            $this->transfers->syncDriverBranches(
                $driver,
                array_map('intval', $request->validated('branch_ids')),
                $request->user('platform'),
            );
        } catch (InvalidArgumentException $exception) {
            Toast::error($exception->getMessage());

            return redirect()->back();
        }

        Toast::success('Assigned branch updated.');

        return redirect()->back();
    }
}
