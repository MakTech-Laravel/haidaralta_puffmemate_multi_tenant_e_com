import { Head, setLayoutProps } from '@inertiajs/react';
import RoleController from '@/actions/App/Http/Controllers/Branch/RoleController';
import StaffController from '@/actions/App/Http/Controllers/Branch/StaffController';
import { RoleProfile } from '@/components/admin/role-profile';

type Branch = {
    id: number;
    name: string;
    slug: string;
};

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

export default function BranchRolesShow({
    branch,
    role,
    permissionGroups,
}: {
    branch: Branch;
    role: Role;
    permissionGroups: CoverageGroup[];
}) {
    const canDelete = !role.is_super_admin && role.users.length === 0;

    setLayoutProps({
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: `/branch/${branch.slug}/dashboard`,
            },
            { title: 'Roles', href: `/branch/${branch.slug}/roles` },
            { title: 'Show', href: '#' },
        ],
    });

    return (
        <>
            <Head title={role.name} />

            <RoleProfile
                role={role}
                permissionGroups={permissionGroups}
                indexHref={RoleController.index.url(branch.slug)}
                editHref={RoleController.edit.url({
                    branch: branch.slug,
                    role: role.id,
                })}
                destroyUrl={
                    canDelete
                        ? RoleController.destroy.url({
                              branch: branch.slug,
                              role: role.id,
                          })
                        : undefined
                }
                userHref={(user) =>
                    StaffController.show.url({
                        branch: branch.slug,
                        user: user.id,
                    })
                }
            />
        </>
    );
}
