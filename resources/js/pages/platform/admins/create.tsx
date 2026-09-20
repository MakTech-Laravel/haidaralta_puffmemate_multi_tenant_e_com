import { Head } from '@inertiajs/react';
import AdminController from '@/actions/App/Http/Controllers/Platform/AdminController';
import { AdminForm } from '@/components/admin/admin-form';
import Heading from '@/components/heading';

type RoleOption = {
    value: string;
    label: string;
};

export default function PlatformAdminsCreate({
    roles,
}: {
    roles: RoleOption[];
}) {
    return (
        <>
            <Head title="Add admin" />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <Heading
                    title="Add admin"
                    description="Create a platform administrator account"
                />

                <AdminForm
                    action={AdminController.store.form()}
                    roles={roles}
                    cancelHref={AdminController.index.url()}
                />
            </div>
        </>
    );
}

PlatformAdminsCreate.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Admins', href: '/platform/admins' },
        { title: 'Create', href: '/platform/admins/create' },
    ],
};
