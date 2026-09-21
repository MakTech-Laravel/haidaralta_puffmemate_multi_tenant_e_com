import BranchShiftAssignmentController from '@/actions/App/Http/Controllers/Platform/BranchShiftAssignmentController';
import BranchShiftRosterPeriodController from '@/actions/App/Http/Controllers/Platform/BranchShiftRosterPeriodController';
import BranchShiftTemplateController from '@/actions/App/Http/Controllers/Platform/BranchShiftTemplateController';
import ShiftAssignmentController from '@/actions/App/Http/Controllers/Platform/ShiftAssignmentController';
import ShiftRosterPeriodController from '@/actions/App/Http/Controllers/Platform/ShiftRosterPeriodController';
import ShiftTemplateController from '@/actions/App/Http/Controllers/Platform/ShiftTemplateController';

export type ShiftWorkspace = 'platform' | 'branch';

export function platformShiftWorkspace(
    workspace: ShiftWorkspace = 'platform',
) {
    if (workspace === 'branch') {
        return {
            title: 'Branch shifts',
            description:
                'Duty windows owned by a branch. Platform templates live under Administrator.',
            templates: BranchShiftTemplateController,
            periods: BranchShiftRosterPeriodController,
            assignments: BranchShiftAssignmentController,
            showBranchSelect: true,
        };
    }

    return {
        title: 'Platform shifts',
        description:
            'Duty windows owned by the platform. Branch templates live under Branch management.',
        templates: ShiftTemplateController,
        periods: ShiftRosterPeriodController,
        assignments: ShiftAssignmentController,
        showBranchSelect: false,
    };
}
