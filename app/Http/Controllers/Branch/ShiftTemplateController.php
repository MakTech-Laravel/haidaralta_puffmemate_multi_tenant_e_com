<?php

namespace App\Http\Controllers\Branch;

use App\Enums\ShiftAssignmentStatus;
use App\Enums\ShiftOwnerType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Branch\StoreShiftTemplateRequest;
use App\Http\Requests\Branch\UpdateShiftTemplateRequest;
use App\Models\Branch;
use App\Models\ShiftTemplate;
use App\Services\Workforce\ShiftTemplateService;
use App\Support\Toast;
use App\Support\WorkforcePayload;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class ShiftTemplateController extends Controller
{
    public function __construct(private ShiftTemplateService $templates) {}

    public function index(Branch $branch): Response
    {
        $templates = ShiftTemplate::query()
            ->with('branch:id,name')
            ->withCount([
                'assignments as scheduled_count' => fn ($query) => $query
                    ->where('status', ShiftAssignmentStatus::Scheduled)
                    ->where('branch_id', $branch->id),
            ])
            ->availableToBranch($branch->id)
            ->orderBy('name')
            ->get()
            ->map(fn (ShiftTemplate $template): array => WorkforcePayload::template($template))
            ->values()
            ->all();

        $onDuty = WorkforcePayload::onDutyAtBranch($branch->id);
        $scheduled = WorkforcePayload::scheduledPeopleAtBranch($branch->id);

        return Inertia::render('branch/shifts/index', [
            'branch' => $branch->only(['id', 'name', 'slug']),
            'templates' => $templates,
            'onDuty' => $onDuty,
            'scheduled' => $scheduled,
            'uncovered' => WorkforcePayload::uncoveredToday($templates),
            'stats' => WorkforcePayload::workspaceStats($templates, $onDuty, $scheduled),
        ]);
    }

    public function create(Branch $branch): Response
    {
        return Inertia::render('branch/shifts/create', [
            'branch' => $branch->only(['id', 'name', 'slug']),
            'defaultOwnerType' => ShiftOwnerType::Branch->value,
        ]);
    }

    public function store(StoreShiftTemplateRequest $request, Branch $branch): RedirectResponse
    {
        try {
            $this->templates->create($request->validated(), $request->user('branch'));
        } catch (InvalidArgumentException $exception) {
            Toast::error($exception->getMessage());

            return redirect()->back();
        }

        Toast::success('Shift created.');

        return redirect()->route('branch.shift-templates.index', $branch);
    }

    public function edit(Branch $branch, ShiftTemplate $shiftTemplate): Response
    {
        $this->assertOwnedByBranch($shiftTemplate, $branch);

        return Inertia::render('branch/shifts/edit', [
            'branch' => $branch->only(['id', 'name', 'slug']),
            'template' => WorkforcePayload::template($shiftTemplate),
        ]);
    }

    public function update(UpdateShiftTemplateRequest $request, Branch $branch, ShiftTemplate $shiftTemplate): RedirectResponse
    {
        $this->assertOwnedByBranch($shiftTemplate, $branch);

        try {
            $this->templates->update($shiftTemplate, $request->validated());
        } catch (InvalidArgumentException $exception) {
            Toast::error($exception->getMessage());

            return redirect()->back();
        }

        Toast::success('Shift updated.');

        return redirect()->route('branch.shift-templates.index', $branch);
    }

    private function assertOwnedByBranch(ShiftTemplate $template, Branch $branch): void
    {
        abort_unless($template->branch_id === $branch->id, 404);
    }
}
