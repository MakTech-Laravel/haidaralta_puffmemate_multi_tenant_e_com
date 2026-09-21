<?php

namespace App\Http\Controllers\Platform;

use App\Concerns\ResolvesShiftOwnerWorkspace;
use App\Enums\ShiftOwnerType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\StoreShiftTemplateRequest;
use App\Http\Requests\Platform\UpdateShiftTemplateRequest;
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
    use ResolvesShiftOwnerWorkspace;

    public function __construct(private ShiftTemplateService $templates) {}

    protected function shiftOwner(): ShiftOwnerType
    {
        return ShiftOwnerType::Platform;
    }

    public function index(): Response
    {
        return Inertia::render('platform/shifts/index', [
            'workspace' => $this->shiftWorkspace(),
            'templates' => WorkforcePayload::templatesOwnedBy($this->shiftOwner()),
            'periods' => WorkforcePayload::periodsOwnedBy($this->shiftOwner()),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('platform/shifts/create', [
            'workspace' => $this->shiftWorkspace(),
            'defaultOwnerType' => $this->shiftOwner()->value,
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
        $this->assertOwnedTemplate($shiftTemplate);

        $shiftTemplate->load('branch:id,name');

        return Inertia::render('platform/shifts/edit', [
            'workspace' => $this->shiftWorkspace(),
            'template' => WorkforcePayload::template($shiftTemplate),
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

        Toast::success('Shift template updated.');

        return redirect()->route('platform.shift-templates.index');
    }
}
