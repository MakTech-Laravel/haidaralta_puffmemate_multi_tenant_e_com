import { Head, setLayoutProps } from '@inertiajs/react';
import RoleController from '@/actions/App/Http/Controllers/Branch/RoleController';
import { RoleForm } from '@/components/admin/role-form';
import Heading from '@/components/heading';
import type { PermissionGroup } from '@/components/permission-selector';

type Branch = {
    id: number;
    name: string;
    slug: string;
};

type Role = {
    id: number;
    name: string;
    is_super_admin: boolean;
    permissions: string[];
};

export default function BranchRolesEdit({
    branch,
    role,
    permissionGroups,
}: {
    branch: Branch;
    role: Role;
    permissionGroups: PermissionGroup[];
}) {
    setLayoutProps({
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: `/branch/${branch.slug}/dashboard`,
            },
            { title: 'Roles', href: `/branch/${branch.slug}/roles` },
            { title: 'Edit', href: '#' },
        ],
    });

    return (
        <>
            <Head title={`Edit ${role.name}`} />

            <div className="mx-auto max-w-3xl space-y-4 p-2">
                <Heading
                    title={`Edit ${role.name}`}
                    description={
                        role.is_super_admin
                            ? 'Super-admin cannot be renamed. Permissions are granted via gate bypass.'
                            : `Update the role for ${branch.name}`
                    }
                />

                <RoleForm
                    action={RoleController.update.form({
                        branch: branch.slug,
                        role: role.id,
                    })}
                    groups={permissionGroups}
                    isEdit
                    locked={role.is_super_admin}
                    defaults={{
                        name: role.name,
                        permissions: role.permissions,
                    }}
                    cancelHref={RoleController.show.url({
                        branch: branch.slug,
                        role: role.id,
                    })}
                />
            </div>
        </>
    );
}
