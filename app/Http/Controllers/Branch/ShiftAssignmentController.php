<?php

namespace App\Http\Controllers\Branch;

use App\DataTables\ShiftAssignmentDataTable;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Branch\ApplyShiftAssignmentChangesRequest;
use App\Http\Requests\Branch\ShiftAssignmentPeopleOptionsRequest;
use App\Http\Requests\Branch\ShiftAssignmentWorkspaceRequest;
use App\Models\Branch;
use App\Models\ShiftTemplate;
use App\Models\User;
use App\Models\UserShiftAssignment;
use App\Services\Workforce\AssignShiftService;
use App\Support\DateTimeFormat;
use App\Support\Toast;
use App\Support\WorkforcePayload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
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

    public function create(ShiftAssignmentWorkspaceRequest $request, Branch $branch): Response
    {
        $validated = $request->validated();

        return Inertia::render('branch/shifts/assign', [
            'branch' => $branch->only(['id', 'name', 'slug']),
            ...WorkforcePayload::shiftAssignmentWorkspace(
                $branch,
                isset($validated['people_kind']) ? (string) $validated['people_kind'] : null,
                isset($validated['user_id']) ? (int) $validated['user_id'] : null,
                isset($validated['month']) ? (string) $validated['month'] : null,
                (int) ($validated['assignment_page'] ?? 1),
                null,
            ),
        ]);
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

    public function store(ApplyShiftAssignmentChangesRequest $request, Branch $branch): RedirectResponse
    {
        $validated = $request->validated();
        $user = User::query()->findOrFail((int) $validated['user_id']);
        $template = isset($validated['shift_template_id'])
            ? ShiftTemplate::query()->findOrFail((int) $validated['shift_template_id'])
            : null;

        try {
            $this->assignments->applyChanges(
                $user,
                $branch,
                $template,
                $validated['assign_dates'] ?? [],
                $validated['replace_day_ids'] ?? [],
                $validated['remove_day_ids'] ?? [],
                $request->user('branch'),
            );
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['schedule' => $exception->getMessage()]);
        }

        Toast::success('Shift schedule updated.');

        return redirect()->route('branch.shift-assignments.create', [
            'branch' => $branch->slug,
            'people_kind' => $user->user_type === UserType::DRIVER ? 'driver' : 'staff',
            'user_id' => $user->id,
            'month' => $validated['month'] ?? DateTimeFormat::now()->format('Y-m'),
            'assignment_page' => $validated['assignment_page'] ?? 1,
        ])->setStatusCode(303);
    }

    public function edit(Branch $branch, UserShiftAssignment $userShiftAssignment): RedirectResponse
    {
        abort_unless($userShiftAssignment->branch_id === $branch->id, 404);
        $userShiftAssignment->loadMissing('user');
        $user = $userShiftAssignment->user;

        abort_unless($user instanceof User, 404);

        return redirect()->route('branch.shift-assignments.create', [
            'branch' => $branch->slug,
            'people_kind' => $user->user_type === UserType::DRIVER ? 'driver' : 'staff',
            'user_id' => $user->id,
            'month' => $userShiftAssignment->start_date->format('Y-m'),
        ]);
    }
}
