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

type StaffMember = {
    id: number;
    name: string;
    phone: string;
    email: string | null;
    is_active: boolean;
    branch_id: number;
    roles: string[];
};

export default function PlatformBranchScopedStaffEdit({
    branch,
    staffMember,
    branches,
    rolesByBranch,
    lockedSuperAdmin,
}: {
    branch: Branch;
    staffMember: StaffMember;
    branches: BranchOption[];
    rolesByBranch: Record<string, RoleOption[]>;
    lockedSuperAdmin: boolean;
}) {
    return (
        <>
            <Head title={`Edit ${staffMember.name}`} />

            <div className="mx-auto flex max-w-2xl flex-col gap-4 p-2">
                <Heading
                    title={`Edit ${staffMember.name}`}
                    description={`Update staff for ${branch.name}`}
                />

                <StaffForm
                    action={BranchScopedStaffController.update.form([
                        branch.slug,
                        staffMember.id,
                    ])}
                    isEdit
                    branches={branches}
                    rolesByBranch={rolesByBranch}
                    branchLocked
                    lockedSuperAdmin={lockedSuperAdmin}
                    defaults={{
                        name: staffMember.name,
                        phone: staffMember.phone,
                        email: staffMember.email,
                        roles: staffMember.roles,
                        is_active: staffMember.is_active,
                        branch_id: staffMember.branch_id,
                    }}
                    cancelHref={BranchScopedStaffController.show.url([
                        branch.slug,
                        staffMember.id,
                    ])}
                />
            </div>
        </>
    );
}

PlatformBranchScopedStaffEdit.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Branches', href: '/platform/branches' },
        { title: 'Staff', href: '#' },
        { title: 'Edit', href: '#' },
    ],
};
