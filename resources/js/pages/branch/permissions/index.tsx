import { Head, setLayoutProps } from '@inertiajs/react';
import { Download } from 'lucide-react';
import PermissionController from '@/actions/App/Http/Controllers/Branch/PermissionController';
import {
    PermissionGroupsTable,
    type PermissionGroup,
} from '@/components/admin/permission-groups-table';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';

type Branch = {
    id: number;
    name: string;
    slug: string;
};

export default function BranchPermissionsIndex({
    branch,
    permissionGroups,
}: {
    branch: Branch;
    permissionGroups: PermissionGroup[];
}) {
    setLayoutProps({
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: `/branch/${branch.slug}/dashboard`,
            },
            {
                title: 'Permissions',
                href: `/branch/${branch.slug}/permissions`,
            },
        ],
    });

    return (
        <>
            <Head title="Permissions" />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Permissions"
                        description={`Read-only catalog of branch permissions for ${branch.name}.`}
                    />
                    <Button variant="outline" asChild>
                        <a href={PermissionController.export.url(branch.slug)}>
                            <Download className="size-4" />
                            Export CSV
                        </a>
                    </Button>
                </div>

                <PermissionGroupsTable permissionGroups={permissionGroups} />
            </div>
        </>
    );
}
