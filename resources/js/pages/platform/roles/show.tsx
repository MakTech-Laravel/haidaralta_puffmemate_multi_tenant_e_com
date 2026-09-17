import { Head } from '@inertiajs/react';
import AdminController from '@/actions/App/Http/Controllers/Platform/AdminController';
import RoleController from '@/actions/App/Http/Controllers/Platform/RoleController';
import { RoleProfile } from '@/components/admin/role-profile';

type Role = {
    id: number;
    name: string;
    is_super_admin: boolean;
    permissions_count: number;
    total_permissions: number;
    users: {
        id: number;
        name: string;
        email: string | null;
    }[];
};

type CoverageGroup = {
    group: string;
    total: number;
    granted_count: number;
    permissions: {
        value: string;
        label: string;
        granted: boolean;
    }[];
};

export default function PlatformRolesShow({
    role,
    permissionGroups,
}: {
    role: Role;
    permissionGroups: CoverageGroup[];
}) {
    const canDelete = !role.is_super_admin && role.users.length === 0;

    return (
        <>
            <Head title={role.name} />

            <RoleProfile
                role={role}
                permissionGroups={permissionGroups}
                indexHref={RoleController.index.url()}
                editHref={RoleController.edit.url(role.id)}
                destroyUrl={
                    canDelete ? RoleController.destroy.url(role.id) : undefined
                }
                userHref={(user) => AdminController.show.url(user.id)}
            />
        </>
    );
}

PlatformRolesShow.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Roles', href: '/platform/roles' },
        { title: 'Show', href: '#' },
    ],
};
