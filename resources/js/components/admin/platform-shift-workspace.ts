import BranchShiftAssignmentController from '@/actions/App/Http/Controllers/Platform/BranchShiftAssignmentController';
import BranchShiftTemplateController from '@/actions/App/Http/Controllers/Platform/BranchShiftTemplateController';
import ShiftAssignmentController from '@/actions/App/Http/Controllers/Platform/ShiftAssignmentController';
import ShiftTemplateController from '@/actions/App/Http/Controllers/Platform/ShiftTemplateController';

export type ShiftWorkspace = 'platform' | 'branch';

export function shiftBranchQuery(branchId?: number | null) {
    if (!branchId) {
        return undefined;
    }

    return { query: { branch_id: branchId } };
}

export function platformShiftWorkspace(
    workspace: ShiftWorkspace = 'platform',
) {
    if (workspace === 'branch') {
        return {
            title: 'Branch shifts',
            description: 'Choose a branch to see that store.',
            templates: BranchShiftTemplateController,
            assignments: BranchShiftAssignmentController,
            showBranchSelect: true,
        };
    }

    return {
        title: 'Platform shifts',
        description:
            'Platform-owned shifts. Branch shifts live under Branch management.',
        templates: ShiftTemplateController,
        assignments: ShiftAssignmentController,
        showBranchSelect: false,
    };
}
