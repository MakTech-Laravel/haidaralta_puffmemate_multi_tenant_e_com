import { Head, router, setLayoutProps } from '@inertiajs/react';
import { ShiftBoard } from '@/components/admin/shift-board';
import {
    platformShiftWorkspace,
    shiftBranchQuery,
    type ShiftWorkspace,
} from '@/components/admin/platform-shift-workspace';
import type {
    BranchOption,
    ShiftAssignmentRow,
    ShiftTemplateOption,
    ShiftWorkspaceStats,
} from '@/components/admin/workforce-types';

export default function PlatformShiftsIndex({
    workspace = 'platform',
    templates,
    scheduled = [],
    onDuty = [],
    stats = {
        on_duty: 0,
        templates: 0,
        active_templates: 0,
        scheduled: 0,
    },
    selectedBranchId = null,
    selectedBranch = null,
}: {
    workspace?: ShiftWorkspace;
    templates: ShiftTemplateOption[];
    scheduled?: ShiftAssignmentRow[];
    onDuty?: ShiftAssignmentRow[];
    stats?: ShiftWorkspaceStats;
    selectedBranchId?: number | null;
    selectedBranch?: BranchOption | null;
}) {
    const routes = platformShiftWorkspace(workspace);
    const branchQuery = shiftBranchQuery(selectedBranchId);
    const branchOptionsUrl =
        workspace === 'branch' ? routes.templates.branchOptions.url() : null;

    setLayoutProps({
        breadcrumbs: [
            { title: 'Dashboard', href: '/platform/dashboard' },
            {
                title: routes.title,
                href: routes.templates.index.url(branchQuery),
            },
        ],
    });

    return (
        <>
            <Head title={routes.title} />
            <ShiftBoard
                kicker={
                    workspace === 'branch'
                        ? 'Branch management'
                        : 'Administrator'
                }
                title={routes.title}
                description={
                    workspace === 'branch'
                        ? selectedBranch
                            ? `Shifts at ${selectedBranch.name}.`
                            : routes.description
                        : routes.description
                }
                templates={templates}
                scheduled={scheduled}
                onDuty={onDuty}
                stats={stats}
                createTemplateHref={routes.templates.create.url(branchQuery)}
                assignHref={routes.assignments.create.url(branchQuery)}
                templateEditHref={(template) =>
                    routes.templates.edit.url(template.id, branchQuery)
                }
                assignmentEditHref={(row) =>
                    routes.assignments.edit.url(row.id, branchQuery)
                }
                selectedBranch={selectedBranch}
                selectedBranchId={selectedBranchId}
                branchOptionsUrl={branchOptionsUrl}
                onSelectBranch={
                    workspace === 'branch'
                        ? (branchId) =>
                              router.get(
                                  routes.templates.index.url({
                                      query: { branch_id: branchId },
                                  }),
                              )
                        : undefined
                }
            />
        </>
    );
}
