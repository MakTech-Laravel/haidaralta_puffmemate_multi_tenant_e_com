<?php

namespace App\Http\Controllers\Platform;

use App\Concerns\ResolvesShiftOwnerWorkspace;
use App\Enums\ShiftOwnerType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\StoreShiftAssignmentRequest;
use App\Http\Requests\Platform\UpdateShiftAssignmentRequest;
use App\Models\UserShiftAssignment;
use App\Services\Workforce\AssignShiftService;
use App\Support\Toast;
use App\Support\WorkforcePayload;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class BranchShiftAssignmentController extends Controller
{
    use ResolvesShiftOwnerWorkspace;

    public function __construct(private AssignShiftService $assignments) {}

    protected function shiftOwner(): ShiftOwnerType
    {
        return ShiftOwnerType::Branch;
    }

    public function create(): Response
    {
        return Inertia::render('platform/shifts/assign', [
            'workspace' => $this->shiftWorkspace(),
            'people' => WorkforcePayload::assignablePeople(),
            'templates' => WorkforcePayload::templatesOwnedBy($this->shiftOwner(), activeOnly: true),
            'periods' => WorkforcePayload::periodsOwnedBy($this->shiftOwner(), activeOnly: true),
            'branches' => WorkforcePayload::branchOptions(),
        ]);
    }

    public function store(StoreShiftAssignmentRequest $request): RedirectResponse
    {
        try {
            $this->assignments->assign($request->validated(), $request->user('platform'), $this->shiftOwner());
        } catch (InvalidArgumentException $exception) {
            Toast::error($exception->getMessage());

            return redirect()->back();
        }

        Toast::success('Shift assigned.');

        return redirect()->route('platform.branch-shift-assignments.create');
    }

    public function update(UpdateShiftAssignmentRequest $request, UserShiftAssignment $userShiftAssignment): RedirectResponse
    {
        $this->assertOwnedAssignment($userShiftAssignment);

        try {
            $this->assignments->change($userShiftAssignment, $request->validated(), $request->user('platform'), $this->shiftOwner());
        } catch (InvalidArgumentException $exception) {
            Toast::error($exception->getMessage());

            return redirect()->back();
        }

        Toast::success('Shift updated.');

        return redirect()->back();
    }
}
