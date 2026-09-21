<?php

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use App\Http\Requests\Branch\StoreShiftAssignmentRequest;
use App\Http\Requests\Branch\UpdateShiftAssignmentRequest;
use App\Models\Branch;
use App\Models\UserShiftAssignment;
use App\Services\Workforce\AssignShiftService;
use App\Support\Toast;
use App\Support\WorkforcePayload;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class ShiftAssignmentController extends Controller
{
    public function __construct(private AssignShiftService $assignments) {}

    public function create(Branch $branch): Response
    {
        return Inertia::render('branch/shifts/assign', [
            'branch' => $branch->only(['id', 'name', 'slug']),
            'people' => WorkforcePayload::assignablePeople($branch->id),
            'templates' => WorkforcePayload::templatesForBranch($branch->id),
            'periods' => WorkforcePayload::periodsForBranch($branch->id),
        ]);
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

        return redirect()->back();
    }
}
