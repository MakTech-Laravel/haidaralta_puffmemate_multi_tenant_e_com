<?php

namespace App\Http\Controllers\Platform;

use App\Concerns\ResolvesShiftOwnerWorkspace;
use App\Enums\ShiftOwnerType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\StoreShiftRosterPeriodRequest;
use App\Http\Requests\Platform\UpdateShiftRosterPeriodRequest;
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
    use ResolvesShiftOwnerWorkspace;

    public function __construct(private ShiftRosterPeriodService $periods) {}

    protected function shiftOwner(): ShiftOwnerType
    {
        return ShiftOwnerType::Platform;
    }

    public function create(): Response
    {
        return Inertia::render('platform/shifts/periods/create', [
            'workspace' => $this->shiftWorkspace(),
            'defaultOwnerType' => $this->shiftOwner()->value,
        ]);
    }

    public function store(StoreShiftRosterPeriodRequest $request): RedirectResponse
    {
        try {
            $this->periods->create($request->validated(), $request->user('platform'));
        } catch (InvalidArgumentException $exception) {
            Toast::error($exception->getMessage());

            return redirect()->back();
        }

        Toast::success('Roster period created.');

        return redirect()->route('platform.shift-templates.index');
    }

    public function edit(ShiftRosterPeriod $shiftRosterPeriod): Response
    {
        $this->assertOwnedPeriod($shiftRosterPeriod);

        $shiftRosterPeriod->load('branch:id,name');

        return Inertia::render('platform/shifts/periods/edit', [
            'workspace' => $this->shiftWorkspace(),
            'period' => WorkforcePayload::period($shiftRosterPeriod),
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

        return redirect()->route('platform.shift-templates.index');
    }
}
