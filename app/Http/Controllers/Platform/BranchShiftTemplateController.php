<?php

namespace App\Http\Controllers\Platform;

use App\Concerns\ResolvesShiftOwnerWorkspace;
use App\Enums\ShiftOwnerType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\StoreBranchShiftTemplateRequest;
use App\Http\Requests\Platform\UpdateShiftTemplateRequest;
use App\Models\ShiftTemplate;
use App\Services\Workforce\ShiftTemplateService;
use App\Support\Toast;
use App\Support\WorkforcePayload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class BranchShiftTemplateController extends Controller
{
    use ResolvesShiftOwnerWorkspace;

    public function __construct(private ShiftTemplateService $templates) {}

    protected function shiftOwner(): ShiftOwnerType
    {
        return ShiftOwnerType::Branch;
    }

    public function index(): Response
    {
        return Inertia::render('platform/shifts/index', $this->workspaceIndexProps());
    }

    public function branchOptions(Request $request): JsonResponse
    {
        return response()->json(WorkforcePayload::paginatedBranchOptions($request));
    }

    public function create(): Response
    {
        $selected = $this->selectedBranch();

        return Inertia::render('platform/shifts/create', [
            'workspace' => $this->shiftWorkspace(),
            'selectedBranchId' => $selected['id'] ?? null,
            'selectedBranch' => $selected,
            'defaultOwnerType' => $this->shiftOwner()->value,
        ]);
    }

    public function store(StoreBranchShiftTemplateRequest $request): RedirectResponse
    {
        try {
            $template = $this->templates->create($request->validated(), $request->user('platform'));
        } catch (InvalidArgumentException $exception) {
            Toast::error($exception->getMessage());

            return redirect()->back();
        }

        Toast::success('Shift created.');

        return redirect()->route(
            'platform.branch-shift-templates.index',
            $this->branchIndexQuery($template->branch_id),
        );
    }

    public function edit(ShiftTemplate $shiftTemplate): Response
    {
        $this->assertOwnedTemplate($shiftTemplate);

        $shiftTemplate->load('branch:id,name');

        return Inertia::render('platform/shifts/edit', [
            'workspace' => $this->shiftWorkspace(),
            'template' => WorkforcePayload::template($shiftTemplate),
            'selectedBranchId' => $shiftTemplate->branch_id,
            'selectedBranch' => WorkforcePayload::branchOption($shiftTemplate->branch_id),
        ]);
    }

    public function update(UpdateShiftTemplateRequest $request, ShiftTemplate $shiftTemplate): RedirectResponse
    {
        $this->assertOwnedTemplate($shiftTemplate);

        try {
            $this->templates->update($shiftTemplate, $request->validated());
        } catch (InvalidArgumentException $exception) {
            Toast::error($exception->getMessage());

            return redirect()->back();
        }

        Toast::success('Shift updated.');

        return redirect()->route(
            'platform.branch-shift-templates.index',
            $this->branchIndexQuery($shiftTemplate->branch_id),
        );
    }
}
