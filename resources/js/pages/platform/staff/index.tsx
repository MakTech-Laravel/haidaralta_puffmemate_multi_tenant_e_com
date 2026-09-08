import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import StaffController from '@/actions/App/Http/Controllers/Platform/StaffController';
import { ConfirmDeleteDialog } from '@/components/admin/confirm-delete-dialog';
import Heading from '@/components/heading';
import { SimplePagination } from '@/components/simple-pagination';
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

export default function PlatformStaffIndex({
    staff,
    filters,
}: {
    staff: PaginatedStaff;
    filters: { search: string };
}) {
    const [search, setSearch] = useState(filters.search);
    const [deletingId, setDeletingId] = useState<number | null>(null);
    const [processing, setProcessing] = useState(false);

    return (
        <>
            <Head title="Staff" />

            <div className="space-y-6 p-2">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Staff"
                        description="Manage platform staff accounts and roles"
                    />
                    <Button asChild>
                        <Link href={StaffController.create.url()}>
                            Add staff
                        </Link>
                    </Button>
                </div>

                <form
                    className="flex max-w-md gap-2"
                    onSubmit={(event) => {
                        event.preventDefault();
                        router.get(
                            StaffController.index.url({
                                query: { search: search || undefined },
                            }),
                            {},
                            { preserveState: true },
                        );
                    }}
                >
                    <Input
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        placeholder="Search staff"
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
                                <TableHead>Email</TableHead>
                                <TableHead>Roles</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="text-right">
                                    Actions
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {staff.data.length === 0 ? (
                                <TableRow>
                                    <TableCell
                                        colSpan={5}
                                        className="text-muted-foreground py-10 text-center"
                                    >
                                        No staff found.
                                    </TableCell>
                                </TableRow>
                            ) : (
                                staff.data.map((member) => (
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
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                asChild
                                            >
                                                <Link
                                                    href={StaffController.show.url(
                                                        member.id,
                                                    )}
                                                >
                                                    View
                                                </Link>
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                asChild
                                            >
                                                <Link
                                                    href={StaffController.edit.url(
                                                        member.id,
                                                    )}
                                                >
                                                    Edit
                                                </Link>
                                            </Button>
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                className="text-destructive"
                                                onClick={() =>
                                                    setDeletingId(member.id)
                                                }
                                            >
                                                Delete
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                </div>

                <SimplePagination links={staff.links} />
            </div>

            <ConfirmDeleteDialog
                open={deletingId !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setDeletingId(null);
                    }
                }}
                description="This will permanently delete the staff account."
                processing={processing}
                onConfirm={() => {
                    if (deletingId === null) {
                        return;
                    }

                    setProcessing(true);
                    router.delete(StaffController.destroy.url(deletingId), {
                        preserveScroll: true,
                        onFinish: () => {
                            setProcessing(false);
                            setDeletingId(null);
                        },
                    });
                }}
            />
        </>
    );
}

PlatformStaffIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Staff', href: '/platform/staff' },
    ],
};
