import { setLayoutProps } from '@inertiajs/react';
import ShiftAssignmentController from '@/actions/App/Http/Controllers/Branch/ShiftAssignmentController';
import ShiftTemplateController from '@/actions/App/Http/Controllers/Branch/ShiftTemplateController';
import { ShiftAssignWorkspace } from '@/components/admin/shift-assign-workspace';
import type {
    BranchOption,
    ShiftAssignmentWorkspaceProps,
    ShiftAssignmentWorkspaceQuery,
} from '@/components/admin/workforce-types';

export default function BranchShiftsAssign({
    branch,
    selectedBranch,
    selectedPeopleKind,
    selectedPerson,
    templates,
    calendar,
}: { branch: BranchOption } & ShiftAssignmentWorkspaceProps) {
    const peopleOptionsUrl = selectedPeopleKind
        ? ShiftAssignmentController.peopleOptions.url(branch.slug ?? '', {
              query: { people_kind: selectedPeopleKind },
          })
        : null;
    const shiftsUrl = ShiftTemplateController.index.url(branch.slug ?? '');
    const assignedPeopleUrl = ShiftAssignmentController.index.url(
        branch.slug ?? '',
    );

    setLayoutProps({
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: `/branch/${branch.slug}/dashboard`,
            },
            { title: 'Shifts', href: shiftsUrl },
            {
                title: 'Assign',
                href: ShiftAssignmentController.create.url(branch.slug ?? ''),
            },
        ],
    });

    return (
        <ShiftAssignWorkspace
            kicker={branch.name}
            branch={selectedBranch ?? branch}
            branchLocked
            branchOptionsUrl={null}
            peopleOptionsUrl={peopleOptionsUrl}
            createUrl={(query: ShiftAssignmentWorkspaceQuery) => {
                const { branch_id: _branchId, ...branchQuery } = query;

                return ShiftAssignmentController.create.url(branch.slug ?? '', {
                    query: branchQuery,
                });
            }}
            storeUrl={ShiftAssignmentController.store.url(branch.slug ?? '')}
            shiftsUrl={shiftsUrl}
            assignedPeopleUrl={assignedPeopleUrl}
            includeBranchInPayload={false}
            selectedPeopleKind={selectedPeopleKind}
            selectedPerson={selectedPerson}
            templates={templates}
            calendar={calendar}
        />
    );
}
