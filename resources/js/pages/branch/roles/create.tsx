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

export default function BranchRolesCreate({
    branch,
    permissionGroups,
    existingNames,
}: {
    branch: Branch;
    permissionGroups: PermissionGroup[];
    existingNames: string[];
}) {
    setLayoutProps({
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: `/branch/${branch.slug}/dashboard`,
            },
            { title: 'Roles', href: `/branch/${branch.slug}/roles` },
            {
                title: 'Create',
                href: `/branch/${branch.slug}/roles/create`,
            },
        ],
    });

    return (
        <>
            <Head title="Create role" />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <Heading
                    title="Create role"
                    description={`Define a role for ${branch.name}`}
                />

                <RoleForm
                    action={RoleController.store.form(branch.slug)}
                    groups={permissionGroups}
                    existingNames={existingNames}
                    cancelHref={RoleController.index.url(branch.slug)}
                />
            </div>
        </>
    );
}
