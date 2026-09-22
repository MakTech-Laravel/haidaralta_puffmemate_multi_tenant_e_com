<?php

namespace App\Http\Controllers\Branch;

use App\DataTables\ShiftAssignmentDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Branch\ShiftAssignmentPeopleOptionsRequest;
use App\Http\Requests\Branch\StoreShiftAssignmentRequest;
use App\Http\Requests\Branch\UpdateShiftAssignmentRequest;
use App\Models\Branch;
use App\Models\User;
use App\Models\UserShiftAssignment;
use App\Services\Workforce\AssignShiftService;
use App\Support\Toast;
use App\Support\WorkforcePayload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class ShiftAssignmentController extends Controller
{
    public function __construct(private AssignShiftService $assignments) {}

    public function index(Request $request, Branch $branch): Response
    {
        return Inertia::render('branch/shifts/assigned-people', [
            'branch' => $branch->only(['id', 'name', 'slug']),
            'table' => ShiftAssignmentDataTable::forBranchPortal($request, $branch->id),
        ]);
    }

    public function create(Branch $branch): Response
    {
        return Inertia::render('branch/shifts/assign', $this->formProps($branch));
    }

    public function peopleOptions(ShiftAssignmentPeopleOptionsRequest $request, Branch $branch): JsonResponse
    {
        $validated = $request->validated();

        return response()->json(WorkforcePayload::paginatedAssignablePeople(
            branchId: $branch->id,
            peopleKind: (string) $validated['people_kind'],
            search: isset($validated['filter']['search']) ? (string) $validated['filter']['search'] : null,
            perPage: (int) ($validated['per_page'] ?? 20),
        ));
    }

    public function store(StoreShiftAssignmentRequest $request, Branch $branch): RedirectResponse
    {
        try {
            $this->assignments->assign($request->validated(), $request->user('branch'));
        } catch (InvalidArgumentException $exception) {
            Toast::error($exception->getMessage());

            return redirect()->back();
        }

        Toast::success('Shift assigned.');

        return redirect()->route('branch.shift-assignments.create', $branch);
    }

    public function edit(Branch $branch, UserShiftAssignment $userShiftAssignment): Response
    {
        abort_unless($userShiftAssignment->branch_id === $branch->id, 404);

        return Inertia::render('branch/shifts/assign', $this->formProps($branch, $userShiftAssignment));
    }

    public function update(UpdateShiftAssignmentRequest $request, Branch $branch, UserShiftAssignment $userShiftAssignment): RedirectResponse
    {
        abort_unless($userShiftAssignment->branch_id === $branch->id, 404);

        try {
            $this->assignments->change($userShiftAssignment, $request->validated(), $request->user('branch'));
        } catch (InvalidArgumentException $exception) {
            Toast::error($exception->getMessage());

            return redirect()->back();
        }

        Toast::success('Shift updated.');

        return redirect()->route('branch.shift-assignments.edit', [
            'branch' => $branch->slug,
            'user_shift_assignment' => $userShiftAssignment->id,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formProps(Branch $branch, ?UserShiftAssignment $assignment = null): array
    {
        $assignment?->loadMissing(['user', 'shiftTemplate', 'days.shiftTemplate']);
        $user = $assignment?->user;
        $otherDays = $user instanceof User && $assignment !== null
            ? WorkforcePayload::otherDutyDays(
                $user,
                $branch->id,
                $assignment->start_date->format('Y-m-d'),
                $assignment->end_date->format('Y-m-d'),
                $assignment->id,
            )
            : [];

        return [
            'branch' => $branch->only(['id', 'name', 'slug']),
            'people' => WorkforcePayload::assignablePeople($branch->id),
            'templates' => WorkforcePayload::templatesForBranch($branch->id),
            'assignment' => $assignment === null ? null : WorkforcePayload::assignment($assignment),
            'days' => $assignment === null ? [] : WorkforcePayload::assignmentDays($assignment),
            'otherDays' => $otherDays,
            'scheduledDays' => WorkforcePayload::scheduledDutyDays($branch->id, $assignment?->id),
        ];
    }
}
