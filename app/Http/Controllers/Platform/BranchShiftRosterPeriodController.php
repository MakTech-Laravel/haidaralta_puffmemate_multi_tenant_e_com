<?php

namespace App\Http\Controllers\Platform;

use App\Concerns\ResolvesShiftOwnerWorkspace;
use App\Enums\ShiftOwnerType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\StoreBranchShiftRosterPeriodRequest;
use App\Http\Requests\Platform\UpdateShiftRosterPeriodRequest;
use App\Models\ShiftRosterPeriod;
use App\Services\Workforce\ShiftRosterPeriodService;
use App\Support\Toast;
use App\Support\WorkforcePayload;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class BranchShiftRosterPeriodController extends Controller
{
    use ResolvesShiftOwnerWorkspace;

    public function __construct(private ShiftRosterPeriodService $periods) {}

    protected function shiftOwner(): ShiftOwnerType
    {
        return ShiftOwnerType::Branch;
    }

    public function create(): RedirectResponse|Response
    {
        if ($redirect = $this->redirectUnlessBranchSelected()) {
            return $redirect;
        }

        $selected = $this->selectedBranch();

        return Inertia::render('platform/shifts/periods/create', [
            'workspace' => $this->shiftWorkspace(),
            'selectedBranchId' => $selected['id'] ?? null,
            'selectedBranch' => $selected,
            'defaultOwnerType' => $this->shiftOwner()->value,
        ]);
    }

    public function store(StoreBranchShiftRosterPeriodRequest $request): RedirectResponse
    {
        try {
            $period = $this->periods->create($request->validated(), $request->user('platform'));
        } catch (InvalidArgumentException $exception) {
            Toast::error($exception->getMessage());

            return redirect()->back();
        }

        Toast::success('Roster period created.');

        return redirect()->route(
            'platform.branch-shift-templates.index',
            $this->branchIndexQuery($period->branch_id),
        );
    }

    public function edit(ShiftRosterPeriod $shiftRosterPeriod): Response
    {
        $this->assertOwnedPeriod($shiftRosterPeriod);

        $shiftRosterPeriod->load('branch:id,name');

        return Inertia::render('platform/shifts/periods/edit', [
            'workspace' => $this->shiftWorkspace(),
            'period' => WorkforcePayload::period($shiftRosterPeriod),
            'selectedBranchId' => $shiftRosterPeriod->branch_id,
            'selectedBranch' => WorkforcePayload::branchOption($shiftRosterPeriod->branch_id),
        ]);
    }

    public function update(UpdateShiftRosterPeriodRequest $request, ShiftRosterPeriod $shiftRosterPeriod): RedirectResponse
    {
        $this->assertOwnedPeriod($shiftRosterPeriod);

        try {
            $this->periods->update($shiftRosterPeriod, $request->validated());
        } catch (InvalidArgumentException $exception) {
            Toast::error($exception->getMessage());

            return redirect()->back();
        }

        Toast::success('Roster period updated.');

        return redirect()->route(
            'platform.branch-shift-templates.index',
            $this->branchIndexQuery($shiftRosterPeriod->branch_id),
        );
    }
}
