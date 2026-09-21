import { Head, setLayoutProps } from '@inertiajs/react';
import { ShiftPageHeader } from '@/components/admin/shift-page-header';
import { ShiftTemplateForm } from '@/components/admin/shift-template-form';
import {
    platformShiftWorkspace,
    shiftBranchQuery,
    type ShiftWorkspace,
} from '@/components/admin/platform-shift-workspace';
import type { BranchOption } from '@/components/admin/workforce-types';

export default function PlatformShiftsCreate({
    workspace = 'platform',
    branches = [],
    selectedBranchId = null,
    defaultOwnerType,
}: {
    workspace?: ShiftWorkspace;
    branches?: BranchOption[];
    selectedBranchId?: number | null;
    defaultOwnerType: string;
}) {
    const routes = platformShiftWorkspace(workspace);
    const branchQuery = shiftBranchQuery(selectedBranchId);
    const lockBranch =
        workspace === 'branch' && Boolean(selectedBranchId);

    setLayoutProps({
        breadcrumbs: [
            { title: 'Dashboard', href: '/platform/dashboard' },
            {
                title: routes.title,
                href: routes.templates.index.url(branchQuery),
            },
            {
                title: 'Create',
                href: routes.templates.create.url(branchQuery),
            },
        ],
    });

    return (
        <>
            <Head title="Create shift" />

            <div className="flex w-full flex-col gap-6 p-2 md:p-4">
                <ShiftPageHeader
                    kicker="Shift"
                    title="Create shift"
                    description={
                        lockBranch
                            ? 'This shift will belong to the branch you picked.'
                            : routes.description
                    }
                />
                <ShiftTemplateForm
                    action={routes.templates.store.form()}
                    branches={branches}
                    lockOwner
                    showBranchSelect={!lockBranch && routes.showBranchSelect}
                    defaults={{
                        owner_type: defaultOwnerType,
                        branch_id: selectedBranchId,
                    }}
                    cancelHref={routes.templates.index.url(branchQuery)}
                    submitLabel="Create shift"
                />
            </div>
        </>
    );
}
