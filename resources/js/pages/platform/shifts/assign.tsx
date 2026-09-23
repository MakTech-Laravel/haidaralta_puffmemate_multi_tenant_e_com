import { setLayoutProps } from '@inertiajs/react';
import BranchShiftTemplateController from '@/actions/App/Http/Controllers/Platform/BranchShiftTemplateController';
import { ShiftAssignWorkspace } from '@/components/admin/shift-assign-workspace';
import {
    platformShiftWorkspace,
    shiftBranchQuery,
    type ShiftWorkspace,
} from '@/components/admin/platform-shift-workspace';
import type {
    ShiftAssignmentWorkspaceProps,
    ShiftAssignmentWorkspaceQuery,
} from '@/components/admin/workforce-types';

export default function PlatformShiftsAssign({
    workspace = 'platform',
    selectedBranch,
    selectedPeopleKind,
    selectedPerson,
    templates,
    calendar,
}: { workspace?: ShiftWorkspace } & ShiftAssignmentWorkspaceProps) {
    const routes = platformShiftWorkspace(workspace);
    const branchQuery = shiftBranchQuery(selectedBranch?.id);
    const shiftsUrl = routes.templates.index.url(branchQuery);
    const assignedPeopleUrl = routes.assignments.index.url(branchQuery);
    const peopleOptionsUrl =
        selectedBranch && selectedPeopleKind
            ? routes.assignments.peopleOptions.url({
                  query: {
                      branch_id: selectedBranch.id,
                      people_kind: selectedPeopleKind,
                  },
              })
            : null;

    setLayoutProps({
        breadcrumbs: [
            { title: 'Dashboard', href: '/platform/dashboard' },
            { title: routes.title, href: shiftsUrl },
            {
                title: 'Assign',
                href: routes.assignments.create.url(branchQuery),
            },
        ],
    });

    return (
        <ShiftAssignWorkspace
            kicker={
                workspace === 'branch'
                    ? (selectedBranch?.name ?? 'Branch management')
                    : 'Platform coverage'
            }
            branch={selectedBranch}
            branchLocked={false}
            branchOptionsUrl={BranchShiftTemplateController.branchOptions.url()}
            peopleOptionsUrl={peopleOptionsUrl}
            createUrl={(query: ShiftAssignmentWorkspaceQuery) =>
                routes.assignments.create.url({ query })
            }
            storeUrl={routes.assignments.store.url()}
            shiftsUrl={shiftsUrl}
            assignedPeopleUrl={assignedPeopleUrl}
            includeBranchInPayload
            selectedPeopleKind={selectedPeopleKind}
            selectedPerson={selectedPerson}
            templates={templates}
            calendar={calendar}
        />
    );
}
