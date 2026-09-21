<?php

namespace App\Concerns;

use App\Enums\ShiftOwnerType;
use App\Models\ShiftRosterPeriod;
use App\Models\ShiftTemplate;
use App\Models\UserShiftAssignment;

trait ResolvesShiftOwnerWorkspace
{
    abstract protected function shiftOwner(): ShiftOwnerType;

    protected function shiftWorkspace(): string
    {
        return $this->shiftOwner() === ShiftOwnerType::Platform ? 'platform' : 'branch';
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
