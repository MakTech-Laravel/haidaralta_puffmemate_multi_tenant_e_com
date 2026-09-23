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

export default function BranchStaffCreate({
    branch,
    roles,
}: {
    branch: Branch;
    roles: RoleOption[];
}) {
    setLayoutProps({
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: `/branch/${branch.slug}/dashboard`,
            },
            { title: 'Staff', href: `/branch/${branch.slug}/staff` },
            { title: 'Create', href: '#' },
        ],
    });

    return (
        <>
            <Head title="Add staff" />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <Heading
                    title="Add staff"
                    description={`Create a staff member for ${branch.name}`}
                />

                <StaffForm
                    action={StaffController.store.form(branch.slug)}
                    roles={roles}
                    cancelHref={StaffController.index.url(branch.slug)}
                />
            </div>
        </>
    );
}
