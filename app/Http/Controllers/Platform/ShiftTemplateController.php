<?php

namespace App\Http\Controllers\Platform;

use App\Enums\ShiftOwnerType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\StoreShiftTemplateRequest;
use App\Http\Requests\Platform\UpdateShiftTemplateRequest;
use App\Models\ShiftTemplate;
use App\Services\Workforce\ShiftTemplateService;
use App\Support\Toast;
use App\Support\WorkforcePayload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class ShiftTemplateController extends Controller
{
    public function __construct(private ShiftTemplateService $templates) {}

    public function index(Request $request): Response
    {
        $templates = ShiftTemplate::query()
            ->with('branch:id,name')
            ->orderBy('name')
            ->get()
            ->map(fn (ShiftTemplate $template): array => WorkforcePayload::template($template))
            ->values()
            ->all();

        return Inertia::render('platform/shifts/index', [
            'templates' => $templates,
            'periods' => WorkforcePayload::periodsForBranch(),
            'ownerTypes' => WorkforcePayload::ownerTypeOptions(),
            'branches' => WorkforcePayload::branchOptions(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('platform/shifts/create', [
            'ownerTypes' => WorkforcePayload::ownerTypeOptions(),
            'branches' => WorkforcePayload::branchOptions(),
            'defaultOwnerType' => ShiftOwnerType::Platform->value,
        ]);
    }

    public function store(StoreShiftTemplateRequest $request): RedirectResponse
    {
        try {
            $this->templates->create($request->validated(), $request->user('platform'));
        } catch (InvalidArgumentException $exception) {
            Toast::error($exception->getMessage());

            return redirect()->back();
        }

        Toast::success('Shift template created.');

        return redirect()->route('platform.shift-templates.index');
    }

    public function edit(ShiftTemplate $shiftTemplate): Response
    {
        $shiftTemplate->load('branch:id,name');

        return Inertia::render('platform/shifts/edit', [
            'template' => WorkforcePayload::template($shiftTemplate),
            'ownerTypes' => WorkforcePayload::ownerTypeOptions(),
            'branches' => WorkforcePayload::branchOptions(),
        ]);
    }

    public function update(UpdateShiftTemplateRequest $request, ShiftTemplate $shiftTemplate): RedirectResponse
    {
        try {
            $this->templates->update($shiftTemplate, $request->validated());
        } catch (InvalidArgumentException $exception) {
            Toast::error($exception->getMessage());

            return redirect()->back();
        }

        Toast::success('Shift template updated.');

        return redirect()->route('platform.shift-templates.index');
    }
}
