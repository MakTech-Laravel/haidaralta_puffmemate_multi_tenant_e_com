<?php

namespace App\Http\Controllers\Platform;

use App\Enums\DriverAssignmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Branch\AssignDriverRequest;
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

class BranchDriverController extends Controller
{
    public function __construct(private DriverService $drivers) {}

    public function index(Request $request, Branch $branch): RedirectResponse
    {
        return redirect()->route('platform.drivers.index', array_replace_recursive(
            $request->query(),
            ['filter' => ['branch_id' => $branch->id]],
        ));
    }

    public function create(Branch $branch): Response
    {
        return Inertia::render('platform/branches/drivers/create', [
            'branch' => $branch->only(['id', 'name', 'slug']),
            'vehicleTypes' => DriverPayload::vehicleTypeOptions(),
            'availabilityStatuses' => DriverPayload::availabilityOptions(),
            'rosterStatuses' => DriverPayload::rosterStatusOptions(),
        ]);
    }

    public function store(StoreDriverRequest $request, Branch $branch): RedirectResponse
    {
        try {
            $driver = $this->drivers->create(
                $request->validated(),
                assignedBy: $request->user('platform'),
                autoAssignBranch: $branch,
                avatar: $request->file('avatar'),
                vehicleImage: $request->file('vehicle_image'),
            );
        } catch (InvalidArgumentException $exception) {
            Toast::error($exception->getMessage());

            return redirect()->back();
        }

        Toast::success('Driver created and assigned to this branch.');

        return redirect()->route('platform.branches.drivers.show', [$branch, $driver]);
    }

    public function show(Branch $branch, User $user): Response
    {
        $driver = $this->resolveAssignedDriver($branch, $user);

        return Inertia::render('platform/branches/drivers/show', [
            'branch' => $branch->only(['id', 'name', 'slug']),
            'driver' => DriverPayload::detail($driver, $branch),
            'rosterStatuses' => DriverPayload::rosterStatusOptions(),
        ]);
    }

    public function edit(Branch $branch, User $user): Response
    {
        $driver = $this->resolveAssignedDriver($branch, $user);

        return Inertia::render('platform/branches/drivers/edit', [
            'branch' => $branch->only(['id', 'name', 'slug']),
            'driver' => DriverPayload::detail($driver, $branch),
            'vehicleTypes' => DriverPayload::vehicleTypeOptions(),
            'availabilityStatuses' => DriverPayload::availabilityOptions(),
            'rosterStatuses' => DriverPayload::rosterStatusOptions(),
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

        return redirect()->route('platform.branches.drivers.show', [$branch, $driver]);
    }

    public function assign(AssignDriverRequest $request, Branch $branch): RedirectResponse
    {
        $driver = DriverPayload::resolveDriver(
            User::query()->findOrFail($request->validated('driver_id')),
        );

        $status = DriverAssignmentStatus::tryFrom((string) ($request->validated('status') ?? DriverAssignmentStatus::Active->value))
            ?? DriverAssignmentStatus::Active;

        $this->drivers->assignToBranch($driver, $branch, $request->user('platform'), $status);

        Toast::success('Driver assigned to branch.');

        return redirect()->back();
    }

    private function resolveAssignedDriver(Branch $branch, User $user): User
    {
        $driver = DriverPayload::resolveDriver($user);

        $assigned = $driver->driverBranchAssignments()
            ->where('branch_id', $branch->id)
            ->exists();

        abort_unless($assigned, 404);

        return $driver;
    }
}
