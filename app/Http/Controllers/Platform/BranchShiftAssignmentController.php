<?php

namespace App\Http\Controllers\Platform;

use App\Concerns\ResolvesShiftOwnerWorkspace;
use App\DataTables\ShiftAssignmentDataTable;
use App\Enums\ShiftOwnerType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\StoreShiftAssignmentRequest;
use App\Http\Requests\Platform\UpdateShiftAssignmentRequest;
use App\Models\User;
use App\Models\UserShiftAssignment;
use App\Services\Workforce\AssignShiftService;
use App\Support\Toast;
use App\Support\WorkforcePayload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function index(Request $request): Response
    {
        $selected = $this->selectedBranch();
        $branchId = $selected['id'] ?? null;

        return Inertia::render('platform/shifts/assigned-people', [
            'workspace' => $this->shiftWorkspace(),
            'table' => ShiftAssignmentDataTable::forBranchOwner($request, $branchId),
            'selectedBranchId' => $branchId,
            'selectedBranch' => $selected,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('platform/shifts/assign', $this->formProps());
    }

    public function store(StoreShiftAssignmentRequest $request): RedirectResponse
    {
        try {
            $assignment = $this->assignments->assign($request->validated(), $request->user('platform'), $this->shiftOwner());
        } catch (InvalidArgumentException $exception) {
            Toast::error($exception->getMessage());

            return redirect()->back();
        }

        Toast::success('Shift assigned.');

        return redirect()->route(
            'platform.branch-shift-assignments.create',
            $this->branchIndexQuery($assignment->branch_id),
        );
    }

    public function edit(UserShiftAssignment $userShiftAssignment): RedirectResponse|Response
    {
        $this->assertOwnedAssignment($userShiftAssignment);

        if ($redirect = $this->redirectUnlessBranchSelected()) {
            return $redirect;
        }

        abort_unless(
            $this->selectedBranchId() === null || $this->selectedBranchId() === $userShiftAssignment->branch_id,
            404,
        );

        return Inertia::render('platform/shifts/assign', $this->formProps($userShiftAssignment));
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

        return redirect()->route(
            'platform.branch-shift-assignments.edit',
            array_merge(
                ['user_shift_assignment' => $userShiftAssignment->id],
                $this->branchIndexQuery($userShiftAssignment->branch_id),
            ),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function formProps(?UserShiftAssignment $assignment = null): array
    {
        $selected = $this->selectedBranch();
        $branchId = $assignment?->branch_id ?? ($selected['id'] ?? null);

        if ($assignment === null && $branchId === null) {
            return [
                'workspace' => $this->shiftWorkspace(),
                'people' => [],
                'templates' => [],
                'branches' => [],
                'selectedBranchId' => null,
                'selectedBranch' => null,
                'assignment' => null,
                'days' => [],
                'otherDays' => [],
                'scheduledDays' => [],
            ];
        }

        $assignment?->loadMissing(['user', 'shiftTemplate', 'days.shiftTemplate']);
        $user = $assignment?->user;
        $otherDays = $user instanceof User && $assignment !== null
            ? WorkforcePayload::otherDutyDays(
                $user,
                $assignment->branch_id,
                $assignment->start_date->format('Y-m-d'),
                $assignment->end_date->format('Y-m-d'),
                $assignment->id,
            )
            : [];

        return [
            'workspace' => $this->shiftWorkspace(),
            'people' => WorkforcePayload::assignablePeople($branchId),
            'templates' => WorkforcePayload::templatesOwnedBy($this->shiftOwner(), activeOnly: true, branchId: $branchId),
            'branches' => $selected === null ? [] : [$selected],
            'selectedBranchId' => $branchId,
            'selectedBranch' => $selected ?? WorkforcePayload::branchOption($branchId),
            'assignment' => $assignment === null ? null : WorkforcePayload::assignment($assignment),
            'days' => $assignment === null ? [] : WorkforcePayload::assignmentDays($assignment),
            'otherDays' => $otherDays,
            'scheduledDays' => WorkforcePayload::scheduledDutyDays($branchId, $assignment?->id),
        ];
    }
}
