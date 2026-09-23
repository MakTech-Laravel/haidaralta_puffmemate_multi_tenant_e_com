<?php

namespace App\Http\Controllers\Branch;

use App\DataTables\BranchDriverDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Branch\StoreDriverRequest;
use App\Http\Requests\Branch\UpdateDriverRequest;
use App\Models\Branch;
use App\Models\User;
use App\Services\Driver\DriverService;
use App\Support\DriverPayload;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class DriverController extends Controller
{
    public function __construct(
        private DriverService $drivers,
    ) {}

    public function index(Request $request, Branch $branch): Response
    {
        return Inertia::render('branch/drivers/index', [
            'branch' => $branch->only(['id', 'name', 'slug']),
            'table' => BranchDriverDataTable::makeForBranch($request, $branch),
        ]);
    }

    public function create(Branch $branch): Response
    {
        return Inertia::render('branch/drivers/create', [
            'branch' => $branch->only(['id', 'name', 'slug']),
            'vehicleTypes' => DriverPayload::vehicleTypeOptions(),
            'availabilityStatuses' => DriverPayload::availabilityOptions(),
        ]);
    }

    public function store(StoreDriverRequest $request, Branch $branch): RedirectResponse
    {
        try {
            $driver = $this->drivers->create(
                $request->validated(),
                assignedBy: $request->user('branch'),
                autoAssignBranch: $branch,
                avatar: $request->file('avatar'),
                vehicleImage: $request->file('vehicle_image'),
            );
        } catch (InvalidArgumentException $exception) {
            Toast::error($exception->getMessage());

            return redirect()->back();
        }

        Toast::success('Driver created and assigned to this branch.');

        return redirect()->route('branch.drivers.show', [$branch, $driver]);
    }

    public function show(Branch $branch, User $user): Response
    {
        $driver = $this->resolveAssignedDriver($branch, $user);

        return Inertia::render('branch/drivers/show', [
            'branch' => $branch->only(['id', 'name', 'slug']),
            'driver' => DriverPayload::detail($driver, $branch),
        ]);
    }

    public function edit(Branch $branch, User $user): Response
    {
        $driver = $this->resolveAssignedDriver($branch, $user);

        return Inertia::render('branch/drivers/edit', [
            'branch' => $branch->only(['id', 'name', 'slug']),
            'driver' => DriverPayload::detail($driver, $branch),
            'vehicleTypes' => DriverPayload::vehicleTypeOptions(),
            'availabilityStatuses' => DriverPayload::availabilityOptions(),
        ]);
    }

    public function update(UpdateDriverRequest $request, Branch $branch, User $user): RedirectResponse
    {
        $driver = $this->resolveAssignedDriver($branch, $user);

        try {
            $this->drivers->update(
                $driver,
                $request->validated(),
                avatar: $request->file('avatar'),
                vehicleImage: $request->file('vehicle_image'),
            );
        } catch (InvalidArgumentException $exception) {
            Toast::error($exception->getMessage());

            return redirect()->back();
        }

        Toast::success('Driver updated.');

        return redirect()->route('branch.drivers.show', [$branch, $driver]);
    }

    private function resolveAssignedDriver(Branch $branch, User $user): User
    {
        $driver = DriverPayload::resolveDriver($user);

        abort_unless(
            $driver->driverBranchAssignments()->where('branch_id', $branch->id)->exists(),
            404,
        );

        return $driver;
    }
}
