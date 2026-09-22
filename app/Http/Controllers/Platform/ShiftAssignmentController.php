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

class ShiftAssignmentController extends Controller
{
    use ResolvesShiftOwnerWorkspace;

    public function __construct(private AssignShiftService $assignments) {}

    protected function shiftOwner(): ShiftOwnerType
    {
        return ShiftOwnerType::Platform;
    }

    public function index(Request $request): Response
    {
        return Inertia::render('platform/shifts/assigned-people', [
            'workspace' => $this->shiftWorkspace(),
            'table' => ShiftAssignmentDataTable::forPlatform($request),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('platform/shifts/assign', $this->formProps());
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

        return redirect()->route('platform.shift-assignments.create');
    }

    public function edit(UserShiftAssignment $userShiftAssignment): Response
    {
        $this->assertOwnedAssignment($userShiftAssignment);

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

        return redirect()->route('platform.shift-assignments.edit', $userShiftAssignment);
    }

    /**
     * @return array<string, mixed>
     */
    private function formProps(?UserShiftAssignment $assignment = null): array
    {
        $assignment?->loadMissing(['user', 'shiftTemplate', 'days.shiftTemplate']);

        $people = WorkforcePayload::assignablePeople();
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
            'people' => $people,
            'templates' => WorkforcePayload::templatesOwnedBy($this->shiftOwner(), activeOnly: true),
            'branches' => WorkforcePayload::branchOptions(),
            'assignment' => $assignment === null ? null : WorkforcePayload::assignment($assignment),
            'days' => $assignment === null ? [] : WorkforcePayload::assignmentDays($assignment),
            'otherDays' => $otherDays,
            'scheduledDays' => WorkforcePayload::scheduledDutyDays(null, $assignment?->id),
        ];
    }
}
