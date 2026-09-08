import { Head, setLayoutProps } from '@inertiajs/react';
import StaffController from '@/actions/App/Http/Controllers/Branch/StaffController';
import { StaffForm } from '@/components/admin/staff-form';
import Heading from '@/components/heading';

type Branch = {
    id: number;
    name: string;
    slug: string;
};

type RoleOption = {
    value: string;
    label: string;
};

type StaffMember = {
    id: number;
    name: string;
    email: string;
    is_active: boolean;
    roles: string[];
};

export default function BranchStaffEdit({
    branch,
    roles,
    staffMember,
    lockedSuperAdmin,
}: {
    branch: Branch;
    roles: RoleOption[];
    staffMember: StaffMember;
    lockedSuperAdmin: boolean;
}) {
    setLayoutProps({
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: `/branch/${branch.slug}/dashboard`,
            },
            { title: 'Staff', href: `/branch/${branch.slug}/staff` },
            { title: 'Edit', href: '#' },
        ],
    });

    return (
        <>
            <Head title={`Edit ${staffMember.name}`} />

            <div className="mx-auto max-w-2xl space-y-6 p-2">
                <Heading
                    title={`Edit ${staffMember.name}`}
                    description={`Update staff for ${branch.name}`}
                />

                <StaffForm
                    action={StaffController.update.form({
                        branch: branch.slug,
                        user: staffMember.id,
                    })}
                    roles={roles}
                    isEdit
                    lockedSuperAdmin={lockedSuperAdmin}
                    defaults={{
                        name: staffMember.name,
                        email: staffMember.email,
                        roles: staffMember.roles,
                        is_active: staffMember.is_active,
                    }}
                    cancelHref={StaffController.show.url({
                        branch: branch.slug,
                        user: staffMember.id,
                    })}
                />
            </div>
        </>
    );
}
