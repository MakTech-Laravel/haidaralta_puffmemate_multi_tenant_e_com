import { Form, Head, Link } from '@inertiajs/react';
import StaffController from '@/actions/App/Http/Controllers/Branch/StaffController';
import Heading from '@/components/heading';
import { SimplePagination } from '@/components/simple-pagination';
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

type StaffRow = {
    id: number;
    name: string;
    email: string;
    is_active: boolean;
    roles: string[];
};

type PaginatedStaff = {
    data: StaffRow[];
    links: {
        url: string | null;
        label: string;
        active: boolean;
    }[];
};

type Branch = {
    id: number;
    name: string;
    slug: string;
};

export default function BranchStaffIndex({
    staff,
    branch,
}: {
    staff: PaginatedStaff;
    branch: Branch;
}) {
    return (
        <>
            <Head title="Staff" />

            <div className="space-y-6 p-2">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Staff"
                        description={`Manage staff for ${branch.name}`}
                    />
                    <Button asChild>
                        <Link href={StaffController.create.url(branch.slug)}>
                            Add staff
                        </Link>
                    </Button>
                </div>

                <div className="rounded-xl border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Name</TableHead>
                                <TableHead>Email</TableHead>
                                <TableHead>Roles</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="text-right">
                                    Actions
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {staff.data.map((member) => (
                                <TableRow key={member.id}>
                                    <TableCell className="font-medium">
                                        {member.name}
                                    </TableCell>
                                    <TableCell>{member.email}</TableCell>
                                    <TableCell>
                                        <div className="flex flex-wrap gap-1">
                                            {member.roles.map((role) => (
                                                <Badge
                                                    key={role}
                                                    variant="secondary"
                                                >
                                                    {role}
                                                </Badge>
                                            ))}
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        {member.is_active
                                            ? 'Active'
                                            : 'Inactive'}
                                    </TableCell>
                                    <TableCell className="space-x-2 text-right">
                                        <Button variant="ghost" size="sm" asChild>
                                            <Link
                                                href={StaffController.edit.url({
                                                    branch: branch.slug,
                                                    user: member.id,
                                                })}
                                            >
                                                Edit
                                            </Link>
                                        </Button>
                                        <Form
                                            {...StaffController.destroy.form({
                                                branch: branch.slug,
                                                user: member.id,
                                            })}
                                            options={{ preserveScroll: true }}
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
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>

                <SimplePagination links={staff.links} />
            </div>
        </>
    );
}

BranchStaffIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/branch/downtown/dashboard' },
        { title: 'Staff', href: '#' },
    ],
};
