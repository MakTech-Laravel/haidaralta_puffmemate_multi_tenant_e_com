import { Head } from '@inertiajs/react';
import BranchScopedStaffController from '@/actions/App/Http/Controllers/Platform/BranchScopedStaffController';
import { StaffForm } from '@/components/admin/staff-form';
import Heading from '@/components/heading';

type Branch = {
    id: number;
    name: string;
    slug: string;
    code: string;
};

type BranchOption = {
    value: string;
    label: string;
};

type RoleOption = {
    value: string;
    label: string;
};

export default function PlatformBranchScopedStaffCreate({
    branch,
    branches,
    rolesByBranch,
}: {
    branch: Branch;
    branches: BranchOption[];
    rolesByBranch: Record<string, RoleOption[]>;
}) {
    return (
        <>
            <Head title={`Add staff · ${branch.name}`} />

            <div className="mx-auto flex max-w-2xl flex-col gap-6 p-2">
                <Heading
                    title="Add staff"
                    description={`Create a staff account for ${branch.name}`}
                />

                <StaffForm
                    action={BranchScopedStaffController.store.form(branch.slug)}
                    branches={branches}
                    rolesByBranch={rolesByBranch}
                    branchLocked
                    defaults={{ branch_id: branch.id }}
                    cancelHref={BranchScopedStaffController.index.url(
                        branch.slug,
                    )}
                    submitLabel="Create staff"
                />
            </div>
        </>
    );
}

PlatformBranchScopedStaffCreate.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Branches', href: '/platform/branches' },
        { title: 'Staff', href: '#' },
        { title: 'Create', href: '#' },
    ],
};
