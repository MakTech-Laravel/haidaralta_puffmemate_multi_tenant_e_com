import { Head } from '@inertiajs/react';
import { Download } from 'lucide-react';
import PermissionController from '@/actions/App/Http/Controllers/Platform/PermissionController';
import {
    PermissionGroupsTable,
    type PermissionGroup,
} from '@/components/admin/permission-groups-table';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';

export default function PlatformPermissionsIndex({
    permissionGroups,
}: {
    permissionGroups: PermissionGroup[];
}) {
    return (
        <>
            <Head title="Permissions" />

            <div className="space-y-6 p-2">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Permissions"
                        description="Read-only catalog of platform permissions. Permissions are defined in code and seeded — not created here."
                    />
                    <Button variant="outline" asChild>
                        <a href={PermissionController.export.url()}>
                            <Download className="h-4 w-4" />
                            Export CSV
                        </a>
                    </Button>
                </div>

                <PermissionGroupsTable permissionGroups={permissionGroups} />
            </div>
        </>
    );
}

PlatformPermissionsIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Permissions', href: '/platform/permissions' },
    ],
};
