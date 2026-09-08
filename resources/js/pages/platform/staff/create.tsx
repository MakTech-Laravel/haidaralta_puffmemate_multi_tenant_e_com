import { Head } from '@inertiajs/react';
import StaffController from '@/actions/App/Http/Controllers/Platform/StaffController';
import { StaffForm } from '@/components/admin/staff-form';
import Heading from '@/components/heading';

type RoleOption = {
    value: string;
    label: string;
};

export default function PlatformStaffCreate({
    roles,
}: {
    roles: RoleOption[];
}) {
    return (
        <>
            <Head title="Add staff" />

            <div className="mx-auto max-w-2xl space-y-6 p-2">
                <Heading
                    title="Add staff"
                    description="Create a platform staff account"
                />

                <StaffForm
                    action={StaffController.store.form()}
                    roles={roles}
                    cancelHref={StaffController.index.url()}
                />
            </div>
        </>
    );
}

PlatformStaffCreate.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Staff', href: '/platform/staff' },
        { title: 'Create', href: '/platform/staff/create' },
    ],
};
