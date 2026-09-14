import { Head } from '@inertiajs/react';
import RoleController from '@/actions/App/Http/Controllers/Platform/RoleController';
import { RoleForm } from '@/components/admin/role-form';
import Heading from '@/components/heading';
import type { PermissionGroup } from '@/components/permission-selector';

export default function PlatformRolesCreate({
    permissionGroups,
}: {
    permissionGroups: PermissionGroup[];
}) {
    return (
        <>
            <Head title="Create role" />

            <div className="mx-auto max-w-3xl space-y-4 p-2">
                <Heading
                    title="Create role"
                    description="Define a platform role and assign permissions"
                />

                <RoleForm
                    action={RoleController.store.form()}
                    groups={permissionGroups}
                    cancelHref={RoleController.index.url()}
                />
            </div>
        </>
    );
}

PlatformRolesCreate.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Roles', href: '/platform/roles' },
        { title: 'Create', href: '/platform/roles/create' },
    ],
};
