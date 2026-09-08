import { Head, setLayoutProps } from '@inertiajs/react';
import { Download } from 'lucide-react';
import { useMemo, useState } from 'react';
import PermissionController from '@/actions/App/Http/Controllers/Branch/PermissionController';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

type Branch = {
    id: number;
    name: string;
    slug: string;
};

type PermissionGroup = {
    group: string;
    permissions: {
        value: string;
        label: string;
        roles_count: number;
    }[];
};

export default function BranchPermissionsIndex({
    branch,
    permissionGroups,
}: {
    branch: Branch;
    permissionGroups: PermissionGroup[];
}) {
    const [search, setSearch] = useState('');

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

    const filteredGroups = useMemo(() => {
        const q = search.trim().toLowerCase();

        if (q === '') {
            return permissionGroups;
        }

        return permissionGroups
            .map((group) => ({
                ...group,
                permissions: group.permissions.filter(
                    (permission) =>
                        permission.label.toLowerCase().includes(q) ||
                        permission.value.toLowerCase().includes(q) ||
                        group.group.toLowerCase().includes(q),
                ),
            }))
            .filter((group) => group.permissions.length > 0);
    }, [permissionGroups, search]);

    return (
        <>
            <Head title="Permissions" />

            <div className="space-y-6 p-2">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Permissions"
                        description={`Read-only catalog of branch permissions for ${branch.name}.`}
                    />
                    <Button variant="outline" asChild>
                        <a href={PermissionController.export.url(branch.slug)}>
                            <Download className="h-4 w-4" />
                            Export CSV
                        </a>
                    </Button>
                </div>

                <Input
                    value={search}
                    onChange={(event) => setSearch(event.target.value)}
                    placeholder="Search permissions"
                    className="max-w-md"
                />

                {filteredGroups.length === 0 ? (
                    <p className="text-muted-foreground text-sm">
                        No permissions match “{search}”.
                    </p>
                ) : (
                    filteredGroups.map((group) => (
                        <section key={group.group} className="space-y-3">
                            <h2 className="text-sm font-medium">
                                {group.group}
                            </h2>
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
                    ))
                )}
            </div>
        </>
    );
}
