import { Form, Head, Link } from '@inertiajs/react';
import RoleController from '@/actions/App/Http/Controllers/Platform/RoleController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

type Role = {
    id: number;
    name: string;
    is_super_admin: boolean;
    permissions: string[];
    users: {
        id: number;
        name: string;
        email: string;
    }[];
};

export default function PlatformRolesShow({ role }: { role: Role }) {
    return (
        <>
            <Head title={role.name} />

            <div className="space-y-6 p-2">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title={role.name}
                        description="Role permissions and assigned users"
                    />
                    <div className="flex gap-2">
                        <Button variant="secondary" asChild>
                            <Link href={RoleController.index.url()}>Back</Link>
                        </Button>
                        <Button asChild>
                            <Link href={RoleController.edit.url(role.id)}>
                                Edit
                            </Link>
                        </Button>
                    </div>
                </div>

                <section className="space-y-3">
                    <h2 className="text-sm font-medium">Permissions</h2>
                    {role.is_super_admin ? (
                        <p className="text-muted-foreground text-sm">
                            Super-admin bypasses all permission checks via Gate.
                        </p>
                    ) : role.permissions.length === 0 ? (
                        <p className="text-muted-foreground text-sm">
                            No permissions assigned.
                        </p>
                    ) : (
                        <div className="flex flex-wrap gap-2">
                            {role.permissions.map((permission) => (
                                <Badge key={permission} variant="secondary">
                                    {permission}
                                </Badge>
                            ))}
                        </div>
                    )}
                </section>

                <section className="space-y-3">
                    <h2 className="text-sm font-medium">Assigned users</h2>
                    <div className="rounded-xl border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Name</TableHead>
                                    <TableHead>Email</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {role.users.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={2}>
                                            No users assigned.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    role.users.map((user) => (
                                        <TableRow key={user.id}>
                                            <TableCell>{user.name}</TableCell>
                                            <TableCell>{user.email}</TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </div>
                </section>

                {!role.is_super_admin && role.users.length === 0 && (
                    <Form
                        {...RoleController.destroy.form(role.id)}
                        options={{ preserveScroll: true }}
                    >
                        {({ processing, errors }) => (
                            <div className="space-y-2">
                                <Button
                                    type="submit"
                                    variant="destructive"
                                    disabled={processing}
                                >
                                    Delete role
                                </Button>
                                <InputError message={errors.role} />
                            </div>
                        )}
                    </Form>
                )}
            </div>
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
