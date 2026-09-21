<?php

namespace App\Concerns;

use App\Enums\ShiftOwnerType;
use App\Models\ShiftRosterPeriod;
use App\Models\ShiftTemplate;
use App\Models\UserShiftAssignment;
use App\Support\WorkforcePayload;
use Illuminate\Http\RedirectResponse;

trait ResolvesShiftOwnerWorkspace
{
    abstract protected function shiftOwner(): ShiftOwnerType;

    protected function shiftWorkspace(): string
    {
        return $this->shiftOwner() === ShiftOwnerType::Platform ? 'platform' : 'branch';
    }

    /**
     * @return array<string, mixed>
     */
    protected function workspaceIndexProps(): array
    {
        $selected = $this->selectedBranch();
        $branchId = $selected['id'] ?? null;
        $templates = WorkforcePayload::templatesOwnedBy($this->shiftOwner(), false, $branchId);
        $periods = WorkforcePayload::periodsOwnedBy($this->shiftOwner(), false, $branchId);
        $onDuty = WorkforcePayload::onDutyOwnedBy($this->shiftOwner(), null, $branchId);

        return [
            'workspace' => $this->shiftWorkspace(),
            'templates' => $templates,
            'periods' => $periods,
            'onDuty' => $onDuty,
            'stats' => WorkforcePayload::workspaceStats($templates, $periods, $onDuty),
            'selectedBranchId' => $branchId,
            'selectedBranch' => $selected,
        ];
    }

    /**
     * @return array{id: int, name: string, slug: string}|null
     */
    protected function selectedBranch(): ?array
    {
        if ($this->shiftOwner() !== ShiftOwnerType::Branch) {
            return null;
        }

        return WorkforcePayload::branchOption((int) request()->query('branch_id', 0));
    }

    protected function redirectUnlessBranchSelected(): ?RedirectResponse
    {
        if ($this->shiftOwner() !== ShiftOwnerType::Branch || $this->selectedBranch() !== null) {
            return null;
        }

        return redirect()->route('platform.branch-shift-templates.index');
    }

    protected function selectedBranchId(): ?int
    {
        return $this->selectedBranch()['id'] ?? null;
    }

    /**
     * @return array{branch_id?: int}
     */
    protected function branchIndexQuery(?int $branchId = null): array
    {
        $id = $branchId ?? $this->selectedBranchId();

        return $id === null ? [] : ['branch_id' => $id];
    }

    protected function assertOwnedTemplate(ShiftTemplate $template): void
    {
        abort_unless($this->matchesShiftOwner($template->isPlatformOwned(), $template->isBranchOwned()), 404);
    }

    protected function assertOwnedPeriod(ShiftRosterPeriod $period): void
    {
        abort_unless($this->matchesShiftOwner($period->isPlatformOwned(), $period->isBranchOwned()), 404);
    }

    protected function assertOwnedAssignment(UserShiftAssignment $assignment): void
    {
        $assignment->loadMissing(['shiftTemplate', 'shiftRosterPeriod']);

        abort_unless($assignment->shiftTemplate !== null && $assignment->shiftRosterPeriod !== null, 404);

        $this->assertOwnedTemplate($assignment->shiftTemplate);
        $this->assertOwnedPeriod($assignment->shiftRosterPeriod);
    }

    private function matchesShiftOwner(bool $isPlatformOwned, bool $isBranchOwned): bool
    {
        return $this->shiftOwner() === ShiftOwnerType::Platform
            ? $isPlatformOwned
            : $isBranchOwned;
    }
}
