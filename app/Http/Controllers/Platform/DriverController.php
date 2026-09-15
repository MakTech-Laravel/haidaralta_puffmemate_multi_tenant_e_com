<?php

namespace App\Http\Controllers\Platform;

use App\DataTables\DriverDataTable;
use App\Enums\DriverAssignmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\StoreDriverRequest;
use App\Http\Requests\Platform\StoreDriverRosterRequest;
use App\Http\Requests\Platform\SyncDriverBranchesRequest;
use App\Http\Requests\Platform\UpdateDriverRequest;
use App\Http\Requests\Platform\UpdateDriverRosterRequest;
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

    public function index(Request $request): Response
    {
        return Inertia::render('platform/drivers/index', [
            'table' => DriverDataTable::make($request),
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
        $driver = $this->drivers->create(
            $request->validated(),
            assignedBy: $request->user('platform'),
        );

        Toast::success('Driver created.');

        return redirect()->route('platform.drivers.show', $driver);
    }

    public function show(User $user): Response
    {
        $driver = DriverPayload::resolveDriver($user);

        return Inertia::render('platform/drivers/show', [
            'driver' => DriverPayload::detail($driver),
            'branches' => DriverPayload::branchOptions(),
            'rosterStatuses' => DriverPayload::rosterStatusOptions(),
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

        $this->drivers->update($driver, $data);

        if (array_key_exists('branch_ids', $data)) {
            $this->drivers->syncBranchAssignments(
                $driver,
                array_map('intval', $data['branch_ids']),
                DriverAssignmentStatus::Active,
                $request->user('platform'),
            );
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

        $this->drivers->syncBranchAssignments(
            $driver,
            array_map('intval', $request->validated('branch_ids')),
            DriverAssignmentStatus::Active,
            $request->user('platform'),
        );

        Toast::success('Branch assignments updated.');

        return redirect()->back();
    }

    public function storeRoster(StoreDriverRosterRequest $request, User $user): RedirectResponse
    {
        $driver = DriverPayload::resolveDriver($user);
        $data = $request->validated();
        $branch = Branch::query()->findOrFail($data['branch_id']);

        try {
            $this->drivers->createRoster($driver, $branch, $data);
        } catch (InvalidArgumentException $exception) {
            Toast::error($exception->getMessage());

            return redirect()->back();
        }

        Toast::success('Roster shift added.');

        return redirect()->back();
    }

    public function updateRoster(UpdateDriverRosterRequest $request, User $user, DriverRoster $roster): RedirectResponse
    {
        $driver = DriverPayload::resolveDriver($user);
        abort_unless($roster->driver_id === $driver->id, 404);

        try {
            $this->drivers->updateRoster($roster, $request->validated());
        } catch (InvalidArgumentException $exception) {
            Toast::error($exception->getMessage());

            return redirect()->back();
        }

        Toast::success('Roster shift updated.');

        return redirect()->back();
    }

    public function destroyRoster(User $user, DriverRoster $roster): RedirectResponse
    {
        $driver = DriverPayload::resolveDriver($user);
        abort_unless($roster->driver_id === $driver->id, 404);

        $this->drivers->deleteRoster($roster);

        Toast::success('Roster shift removed.');

        return redirect()->back();
    }
}
