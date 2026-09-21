<?php

namespace App\Http\Controllers\Platform;

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

class ShiftAssignmentController extends Controller
{
    public function __construct(private AssignShiftService $assignments) {}

    public function create(): Response
    {
        return Inertia::render('platform/shifts/assign', [
            'people' => WorkforcePayload::assignablePeople(),
            'templates' => WorkforcePayload::templatesForBranch(),
            'periods' => WorkforcePayload::periodsForBranch(),
            'branches' => WorkforcePayload::branchOptions(),
        ]);
    }

    public function store(StoreShiftAssignmentRequest $request): RedirectResponse
    {
        try {
            $this->assignments->assign($request->validated(), $request->user('platform'));
        } catch (InvalidArgumentException $exception) {
            Toast::error($exception->getMessage());

            return redirect()->back();
        }

        Toast::success('Shift assigned.');

        return redirect()->route('platform.shift-assignments.create');
    }

    public function update(UpdateShiftAssignmentRequest $request, UserShiftAssignment $userShiftAssignment): RedirectResponse
    {
        try {
            $this->assignments->change($userShiftAssignment, $request->validated(), $request->user('platform'));
        } catch (InvalidArgumentException $exception) {
            Toast::error($exception->getMessage());

            return redirect()->back();
        }

        Toast::success('Shift updated.');

        return redirect()->back();
    }
}
