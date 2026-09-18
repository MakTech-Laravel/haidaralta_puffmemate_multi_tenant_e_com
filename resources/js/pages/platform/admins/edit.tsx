import { Head } from '@inertiajs/react';
import AdminController from '@/actions/App/Http/Controllers/Platform/AdminController';
import { AdminForm } from '@/components/admin/admin-form';
import Heading from '@/components/heading';

type RoleOption = {
    value: string;
    label: string;
};

type Admin = {
    id: number;
    name: string;
    phone: string;
    email: string | null;
    is_active: boolean;
    roles: string[];
    avatar: string | null;
    avatar_url: string | null;
};

export default function PlatformAdminsEdit({
    roles,
    admin,
    lockedSuperAdmin,
}: {
    roles: RoleOption[];
    admin: Admin;
    lockedSuperAdmin: boolean;
}) {
    return (
        <>
            <Head title={`Edit ${admin.name}`} />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <Heading
                    title={`Edit ${admin.name}`}
                    description="Update platform administrator account"
                />

                <AdminForm
                    action={AdminController.update.form(admin.id)}
                    roles={roles}
                    isEdit
                    lockedSuperAdmin={lockedSuperAdmin}
                    defaults={{
                        name: admin.name,
                        phone: admin.phone,
                        email: admin.email,
                        roles: admin.roles,
                        is_active: admin.is_active,
                        avatar: admin.avatar,
                        avatar_url: admin.avatar_url,
                    }}
                    cancelHref={AdminController.show.url(admin.id)}
                />
            </div>
        </>
    );
}

PlatformAdminsEdit.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Admins', href: '/platform/admins' },
        { title: 'Edit', href: '#' },
    ],
};
