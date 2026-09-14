import { Head } from '@inertiajs/react';
import StaffController from '@/actions/App/Http/Controllers/Platform/StaffController';
import { StaffForm } from '@/components/admin/staff-form';
import Heading from '@/components/heading';

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
    roles: string[];
};

export default function PlatformStaffEdit({
    roles,
    staffMember,
    lockedSuperAdmin,
}: {
    roles: RoleOption[];
    staffMember: StaffMember;
    lockedSuperAdmin: boolean;
}) {
    return (
        <>
            <Head title={`Edit ${staffMember.name}`} />

            <div className="mx-auto max-w-2xl space-y-4 p-2">
                <Heading
                    title={`Edit ${staffMember.name}`}
                    description="Update platform staff account"
                />

                <StaffForm
                    action={StaffController.update.form(staffMember.id)}
                    roles={roles}
                    isEdit
                    lockedSuperAdmin={lockedSuperAdmin}
                    defaults={{
                        name: staffMember.name,
                        phone: staffMember.phone,
                        email: staffMember.email,
                        roles: staffMember.roles,
                        is_active: staffMember.is_active,
                    }}
                    cancelHref={StaffController.show.url(staffMember.id)}
                />
            </div>
        </>
    );
}

PlatformStaffEdit.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Staff', href: '/platform/staff' },
        { title: 'Edit', href: '#' },
    ],
};
