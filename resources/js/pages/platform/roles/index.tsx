import { Form, Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import RoleController from '@/actions/App/Http/Controllers/Platform/RoleController';
import Heading from '@/components/heading';
import { SimplePagination } from '@/components/simple-pagination';
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

type RoleRow = {
    id: number;
    name: string;
    permissions_count: number;
    users_count: number;
    is_super_admin: boolean;
};

type PaginatedRoles = {
    data: RoleRow[];
    links: {
        url: string | null;
        label: string;
        active: boolean;
    }[];
};

export default function PlatformRolesIndex({
    roles,
    filters,
}: {
    roles: PaginatedRoles;
    filters: { search: string };
}) {
    const [search, setSearch] = useState(filters.search);

    return (
        <>
            <Head title="Roles" />

            <div className="space-y-6 p-2">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Roles"
                        description="Manage platform roles and their permissions"
                    />
                    <Button asChild>
                        <Link href={RoleController.create.url()}>
                            Create role
                        </Link>
                    </Button>
                </div>

                <form
                    className="flex max-w-md gap-2"
                    onSubmit={(event) => {
                        event.preventDefault();
                        router.get(
                            RoleController.index.url({
                                query: { search },
                            }),
                            {},
                            { preserveState: true },
                        );
                    }}
                >
                    <Input
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        placeholder="Search roles"
                    />
                    <Button type="submit" variant="secondary">
                        Search
                    </Button>
                </form>

                <div className="rounded-xl border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Name</TableHead>
                                <TableHead>Permissions</TableHead>
                                <TableHead>Users</TableHead>
                                <TableHead className="text-right">
                                    Actions
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {roles.data.map((role) => (
                                <TableRow key={role.id}>
                                    <TableCell className="font-medium">
                                        {role.name}
                                    </TableCell>
                                    <TableCell>
                                        {role.permissions_count}
                                    </TableCell>
                                    <TableCell>{role.users_count}</TableCell>
                                    <TableCell className="space-x-2 text-right">
                                        <Button variant="ghost" size="sm" asChild>
                                            <Link
                                                href={RoleController.show.url(
                                                    role.id,
                                                )}
                                            >
                                                View
                                            </Link>
                                        </Button>
                                        <Button variant="ghost" size="sm" asChild>
                                            <Link
                                                href={RoleController.edit.url(
                                                    role.id,
                                                )}
                                            >
                                                Edit
                                            </Link>
                                        </Button>
                                        {!role.is_super_admin &&
                                            role.users_count === 0 && (
                                                <Form
                                                    {...RoleController.destroy.form(
                                                        role.id,
                                                    )}
                                                    options={{
                                                        preserveScroll: true,
                                                    }}
                                                    className="inline"
                                                >
                                                    {({ processing }) => (
                                                        <Button
                                                            type="submit"
                                                            variant="ghost"
                                                            size="sm"
                                                            disabled={processing}
                                                            className="text-destructive"
                                                        >
                                                            Delete
                                                        </Button>
                                                    )}
                                                </Form>
                                            )}
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>

                <SimplePagination links={roles.links} />
            </div>
        </>
    );
}

PlatformRolesIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Roles', href: '/platform/roles' },
    ],
};
