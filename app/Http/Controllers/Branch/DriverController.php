<?php

namespace App\Http\Controllers\Branch;

use App\DataTables\BranchDriverDataTable;
use App\Enums\DriverAssignmentStatus;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Branch\AssignDriverRequest;
use App\Http\Requests\Branch\StoreDriverRequest;
use App\Http\Requests\Branch\StoreDriverRosterRequest;
use App\Http\Requests\Branch\UpdateDriverRequest;
use App\Http\Requests\Branch\UpdateDriverRosterRequest;
use App\Models\Branch;
use App\Models\DriverRoster;
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
    public function __construct(private DriverService $drivers) {}

    public function index(Request $request, Branch $branch): Response
    {
        return Inertia::render('branch/drivers/index', [
            'branch' => $branch->only(['id', 'name', 'slug']),
            'table' => BranchDriverDataTable::makeForBranch($request, $branch),
            'assignableDrivers' => $this->assignableDrivers($branch),
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
        $driver = $this->drivers->create(
            $request->validated(),
            assignedBy: $request->user('branch'),
            autoAssignBranch: $branch,
            avatar: $request->file('avatar'),
            vehicleImage: $request->file('vehicle_image'),
        );

        Toast::success('Driver created and assigned to this branch.');

        return redirect()->route('branch.drivers.show', [$branch, $driver]);
    }

    public function show(Branch $branch, User $user): Response
    {
        $driver = $this->resolveAssignedDriver($branch, $user);

        return Inertia::render('branch/drivers/show', [
            'branch' => $branch->only(['id', 'name', 'slug']),
            'driver' => DriverPayload::detail($driver, $branch),
            'rosterStatuses' => DriverPayload::rosterStatusOptions(),
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

        $this->drivers->update(
            $driver,
            $request->validated(),
            avatar: $request->file('avatar'),
            vehicleImage: $request->file('vehicle_image'),
        );

        Toast::success('Driver updated.');

        return redirect()->route('branch.drivers.show', [$branch, $driver]);
    }

    public function assign(AssignDriverRequest $request, Branch $branch): RedirectResponse
    {
        $driver = DriverPayload::resolveDriver(
            User::query()->findOrFail($request->validated('driver_id')),
        );

        $status = DriverAssignmentStatus::tryFrom((string) ($request->validated('status') ?? DriverAssignmentStatus::Active->value))
            ?? DriverAssignmentStatus::Active;

        $this->drivers->assignToBranch($driver, $branch, $request->user('branch'), $status);

        Toast::success('Driver assigned to branch.');

        return redirect()->back();
    }

    public function storeRoster(StoreDriverRosterRequest $request, Branch $branch, User $user): RedirectResponse
    {
        $driver = $this->resolveAssignedDriver($branch, $user);

        try {
            $this->drivers->createRoster($driver, $branch, $request->validated());
        } catch (InvalidArgumentException $exception) {
            Toast::error($exception->getMessage());

            return redirect()->back();
        }

        Toast::success('Roster shift added.');

        return redirect()->back();
    }

    public function updateRoster(
        UpdateDriverRosterRequest $request,
        Branch $branch,
        User $user,
        DriverRoster $roster,
    ): RedirectResponse {
        $driver = $this->resolveAssignedDriver($branch, $user);
        abort_unless($roster->driver_id === $driver->id && $roster->branch_id === $branch->id, 404);

        try {
            $this->drivers->updateRoster($roster, $request->validated());
        } catch (InvalidArgumentException $exception) {
            Toast::error($exception->getMessage());

            return redirect()->back();
        }

        Toast::success('Roster shift updated.');

        return redirect()->back();
    }

    public function destroyRoster(Branch $branch, User $user, DriverRoster $roster): RedirectResponse
    {
        $driver = $this->resolveAssignedDriver($branch, $user);
        abort_unless($roster->driver_id === $driver->id && $roster->branch_id === $branch->id, 404);

        $this->drivers->deleteRoster($roster);

        Toast::success('Roster shift removed.');

        return redirect()->back();
    }

    /**
     * @return list<array{id: int, name: string, phone: string}>
     */
    private function assignableDrivers(Branch $branch): array
    {
        return User::query()
            ->where('user_type', UserType::DRIVER)
            ->where('is_active', true)
            ->whereDoesntHave(
                'driverBranchAssignments',
                fn ($query) => $query
                    ->where('branch_id', $branch->id)
                    ->where('status', DriverAssignmentStatus::Active->value),
            )
            ->orderBy('name')
            ->get(['id', 'name', 'phone'])
            ->map(fn (User $driver): array => [
                'id' => $driver->id,
                'name' => $driver->name,
                'phone' => $driver->phone,
            ])
            ->all();
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
