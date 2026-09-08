import { Head } from '@inertiajs/react';
import RoleController from '@/actions/App/Http/Controllers/Platform/RoleController';
import { RoleForm } from '@/components/admin/role-form';
import Heading from '@/components/heading';
import type { PermissionGroup } from '@/components/permission-selector';

type Role = {
    id: number;
    name: string;
    is_super_admin: boolean;
    permissions: string[];
};

export default function PlatformRolesEdit({
    role,
    permissionGroups,
}: {
    role: Role;
    permissionGroups: PermissionGroup[];
}) {
    return (
        <>
            <Head title={`Edit ${role.name}`} />

            <div className="mx-auto max-w-3xl space-y-6 p-2">
                <Heading
                    title={`Edit ${role.name}`}
                    description={
                        role.is_super_admin
                            ? 'Super-admin cannot be renamed. Permissions are granted via gate bypass.'
                            : 'Update the role name and assigned permissions'
                    }
                />

                <RoleForm
                    action={RoleController.update.form(role.id)}
                    groups={permissionGroups}
                    isEdit
                    locked={role.is_super_admin}
                    defaults={{
                        name: role.name,
                        permissions: role.permissions,
                    }}
                    cancelHref={RoleController.show.url(role.id)}
                />
            </div>
        </>
    );
}

PlatformRolesEdit.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Roles', href: '/platform/roles' },
        { title: 'Edit', href: '#' },
    ],
};
