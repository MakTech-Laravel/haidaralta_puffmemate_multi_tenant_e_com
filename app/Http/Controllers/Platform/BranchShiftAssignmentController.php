<?php

namespace App\Http\Controllers\Platform;

use App\Concerns\ResolvesShiftOwnerWorkspace;
use App\DataTables\ShiftAssignmentDataTable;
use App\Enums\ShiftOwnerType;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\ApplyShiftAssignmentChangesRequest;
use App\Http\Requests\Platform\ShiftAssignmentPeopleOptionsRequest;
use App\Http\Requests\Platform\ShiftAssignmentWorkspaceRequest;
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

    public function create(ShiftAssignmentWorkspaceRequest $request): Response
    {
        $validated = $request->validated();
        $branch = isset($validated['branch_id'])
            ? Branch::query()->where('status', true)->findOrFail((int) $validated['branch_id'])
            : null;

        return Inertia::render('platform/shifts/assign', [
            'workspace' => $this->shiftWorkspace(),
            ...WorkforcePayload::shiftAssignmentWorkspace(
                $branch,
                isset($validated['people_kind']) ? (string) $validated['people_kind'] : null,
                isset($validated['user_id']) ? (int) $validated['user_id'] : null,
                isset($validated['month']) ? (string) $validated['month'] : null,
                (int) ($validated['assignment_page'] ?? 1),
                $this->shiftOwner(),
            ),
        ]);
    }

    public function peopleOptions(ShiftAssignmentPeopleOptionsRequest $request): JsonResponse
    {
        $validated = $request->validated();

        return response()->json(WorkforcePayload::paginatedAssignablePeople(
            branchId: (int) $validated['branch_id'],
            peopleKind: (string) $validated['people_kind'],
            search: isset($validated['filter']['search']) ? (string) $validated['filter']['search'] : null,
            perPage: (int) ($validated['per_page'] ?? 20),
        ));
    }

    public function store(ApplyShiftAssignmentChangesRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $branch = Branch::query()->where('status', true)->findOrFail((int) $validated['branch_id']);
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
                $request->user('platform'),
                $this->shiftOwner(),
            );
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['schedule' => $exception->getMessage()]);
        }

        Toast::success('Shift schedule updated.');

        return redirect()->route('platform.branch-shift-assignments.create', [
            'branch_id' => $branch->id,
            'people_kind' => $user->user_type === UserType::DRIVER ? 'driver' : 'staff',
            'user_id' => $user->id,
            'month' => $validated['month'] ?? DateTimeFormat::now()->format('Y-m'),
            'assignment_page' => $validated['assignment_page'] ?? 1,
        ])->setStatusCode(303);
    }

    public function edit(UserShiftAssignment $userShiftAssignment): RedirectResponse
    {
        $this->assertOwnedAssignment($userShiftAssignment);
        $userShiftAssignment->loadMissing('user');
        $user = $userShiftAssignment->user;

        abort_unless($user instanceof User, 404);

        return redirect()->route('platform.branch-shift-assignments.create', [
            'branch_id' => $userShiftAssignment->branch_id,
            'people_kind' => $user->user_type === UserType::DRIVER ? 'driver' : 'staff',
            'user_id' => $user->id,
            'month' => $userShiftAssignment->start_date->format('Y-m'),
        ]);
    }
}
