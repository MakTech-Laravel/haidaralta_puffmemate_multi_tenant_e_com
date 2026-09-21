<?php

namespace App\Http\Controllers\Branch;

use App\Enums\ShiftOwnerType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Branch\StoreShiftRosterPeriodRequest;
use App\Http\Requests\Branch\UpdateShiftRosterPeriodRequest;
use App\Models\Branch;
use App\Models\ShiftRosterPeriod;
use App\Services\Workforce\ShiftRosterPeriodService;
use App\Support\Toast;
use App\Support\WorkforcePayload;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class ShiftRosterPeriodController extends Controller
{
    public function __construct(private ShiftRosterPeriodService $periods) {}

    public function create(Branch $branch): Response
    {
        return Inertia::render('branch/shifts/periods/create', [
            'branch' => $branch->only(['id', 'name', 'slug']),
            'defaultOwnerType' => ShiftOwnerType::Branch->value,
        ]);
    }

    public function store(StoreShiftRosterPeriodRequest $request, Branch $branch): RedirectResponse
    {
        try {
            $this->periods->create($request->validated(), $request->user('branch'));
        } catch (InvalidArgumentException $exception) {
            Toast::error($exception->getMessage());

            return redirect()->back();
        }

        Toast::success('Roster period created.');

        return redirect()->route('branch.shift-templates.index', $branch);
    }

    public function edit(Branch $branch, ShiftRosterPeriod $shiftRosterPeriod): Response
    {
        abort_unless($shiftRosterPeriod->branch_id === $branch->id, 404);

        return Inertia::render('branch/shifts/periods/edit', [
            'branch' => $branch->only(['id', 'name', 'slug']),
            'period' => WorkforcePayload::period($shiftRosterPeriod),
        ]);
    }

    public function update(UpdateShiftRosterPeriodRequest $request, Branch $branch, ShiftRosterPeriod $shiftRosterPeriod): RedirectResponse
    {
        abort_unless($shiftRosterPeriod->branch_id === $branch->id, 404);

        try {
            $this->periods->update($shiftRosterPeriod, $request->validated());
        } catch (InvalidArgumentException $exception) {
            Toast::error($exception->getMessage());

            return redirect()->back();
        }

        Toast::success('Roster period updated.');

        return redirect()->route('branch.shift-templates.index', $branch);
    }
}
