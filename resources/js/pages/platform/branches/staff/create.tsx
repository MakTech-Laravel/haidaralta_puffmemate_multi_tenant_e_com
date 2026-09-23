import { Head } from '@inertiajs/react';
import BranchScopedStaffController from '@/actions/App/Http/Controllers/Platform/BranchScopedStaffController';
import { StaffForm } from '@/components/admin/staff-form';
import Heading from '@/components/heading';
import { filteredBranchStaffUrl } from '@/lib/branch-people-query';

type Branch = {
    id: number;
    name: string;
    slug: string;
    code: string;
};

type RoleOption = {
    value: string;
    label: string;
};

type SelectedBranch = {
    value: string;
    label: string;
};

export default function PlatformBranchScopedStaffCreate({
    branch,
    roles,
    selectedBranch,
}: {
    branch: Branch;
    roles: RoleOption[];
    selectedBranch: SelectedBranch;
}) {
    return (
        <>
            <Head title={`Add staff · ${branch.name}`} />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <Heading
                    title="Add staff"
                    description={`Create a staff account for ${branch.name}`}
                />

                <StaffForm
                    action={BranchScopedStaffController.store.form(branch.slug)}
                    roles={roles}
                    selectedBranch={selectedBranch}
                    branchLocked
                    defaults={{ branch_id: branch.id }}
                    cancelHref={filteredBranchStaffUrl(branch.id)}
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
