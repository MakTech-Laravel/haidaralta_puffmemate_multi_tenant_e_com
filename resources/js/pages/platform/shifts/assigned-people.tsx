import { Head, router, setLayoutProps } from '@inertiajs/react';
import BranchShiftTemplateController from '@/actions/App/Http/Controllers/Platform/BranchShiftTemplateController';
import { ShiftAssignedPeoplePage } from '@/components/admin/shift-assigned-people-page';
import type { AssignedPeopleTablePayload } from '@/components/admin/shift-assigned-people-page';
import { ShiftBranchPicker } from '@/components/admin/shift-branch-picker';
import {
    platformShiftWorkspace,
    shiftBranchQuery,
    type ShiftWorkspace,
} from '@/components/admin/platform-shift-workspace';
import type { BranchOption } from '@/components/admin/workforce-types';

export default function PlatformAssignedPeople({
    workspace = 'platform',
    table,
    selectedBranchId = null,
    selectedBranch = null,
}: {
    workspace?: ShiftWorkspace;
    table: AssignedPeopleTablePayload;
    selectedBranchId?: number | null;
    selectedBranch?: BranchOption | null;
}) {
    const routes = platformShiftWorkspace(workspace);
    const branchQuery = shiftBranchQuery(selectedBranchId);
    const branchOptionsUrl =
        workspace === 'branch'
            ? BranchShiftTemplateController.branchOptions.url()
            : null;
    const waitingForBranch = Boolean(
        workspace === 'branch' && branchOptionsUrl && !selectedBranchId,
    );
    const indexUrl = routes.assignments.index.url(branchQuery);

    setLayoutProps({
        breadcrumbs: [
            { title: 'Dashboard', href: '/platform/dashboard' },
            {
                title: routes.title,
                href: routes.templates.index.url(branchQuery),
            },
            {
                title: 'Assigned people',
                href: indexUrl,
            },
        ],
    });

    const branchPicker =
        workspace === 'branch' && branchOptionsUrl ? (
            <ShiftBranchPicker
                optionsUrl={branchOptionsUrl}
                value={selectedBranchId ? String(selectedBranchId) : ''}
                selectedLabel={selectedBranch?.name}
                size={waitingForBranch ? 'lg' : 'default'}
                className={waitingForBranch ? 'max-w-none' : undefined}
                onChange={(branchId) =>
                    router.get(
                        routes.assignments.index.url({
                            query: { branch_id: Number(branchId) },
                        }),
                    )
                }
            />
        ) : null;

    return (
        <>
            <Head title="Assigned people" />
            <ShiftAssignedPeoplePage
                kicker={
                    workspace === 'branch'
                        ? (selectedBranch?.name ?? 'Branch management')
                        : 'Administrator'
                }
                title="Assigned people"
                description="Everyone who has a shift assigned — their name, dates, and the shift."
                tablePayload={table}
                indexUrl={indexUrl}
                extraQuery={
                    selectedBranchId
                        ? { branch_id: selectedBranchId }
                        : undefined
                }
                assignHref={routes.assignments.create.url(branchQuery)}
                editHref={(row) =>
                    routes.assignments.create.url({
                        query: {
                            branch_id: row.branch_id,
                            people_kind:
                                row.user_type === 'driver' ? 'driver' : 'staff',
                            user_id: row.user_id,
                            ...(row.start_date
                                ? { month: row.start_date.slice(0, 7) }
                                : {}),
                        },
                    })
                }
                waitingForBranch={waitingForBranch}
                branchPicker={branchPicker}
            />
        </>
    );
}
