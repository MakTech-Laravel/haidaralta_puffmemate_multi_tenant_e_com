import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

type PermissionGroup = {
    group: string;
    permissions: {
        value: string;
        label: string;
        roles_count: number;
    }[];
};

export default function PlatformPermissionsIndex({
    permissionGroups,
}: {
    permissionGroups: PermissionGroup[];
}) {
    return (
        <>
            <Head title="Permissions" />

            <div className="space-y-6 p-2">
                <Heading
                    title="Permissions"
                    description="Read-only catalog of platform permissions. Permissions are defined in code and seeded — not created here."
                />

                {permissionGroups.map((group) => (
                    <section key={group.group} className="space-y-3">
                        <h2 className="text-sm font-medium">{group.group}</h2>
                        <div className="rounded-xl border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Permission</TableHead>
                                        <TableHead>Key</TableHead>
                                        <TableHead>Roles</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {group.permissions.map((permission) => (
                                        <TableRow key={permission.value}>
                                            <TableCell>
                                                {permission.label}
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant="outline">
                                                    {permission.value}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                {permission.roles_count}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                    </section>
                ))}
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
