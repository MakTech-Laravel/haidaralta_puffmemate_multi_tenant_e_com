import { Head, Link, router, setLayoutProps } from '@inertiajs/react';
import { useState } from 'react';
import StaffController from '@/actions/App/Http/Controllers/Branch/StaffController';
import { ConfirmDeleteDialog } from '@/components/admin/confirm-delete-dialog';
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
    const [deletingId, setDeletingId] = useState<number | null>(null);
    const [processing, setProcessing] = useState(false);

    setLayoutProps({
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: `/branch/${branch.slug}/dashboard`,
            },
            { title: 'Staff', href: `/branch/${branch.slug}/staff` },
        ],
    });

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
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            asChild
                                        >
                                            <Link
                                                href={StaffController.show.url({
                                                    branch: branch.slug,
                                                    user: member.id,
                                                })}
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
                                                href={StaffController.edit.url({
                                                    branch: branch.slug,
                                                    user: member.id,
                                                })}
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
                            ))}
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
                    router.delete(
                        StaffController.destroy.url({
                            branch: branch.slug,
                            user: deletingId,
                        }),
                        {
                            preserveScroll: true,
                            onFinish: () => {
                                setProcessing(false);
                                setDeletingId(null);
                            },
                        },
                    );
                }}
            />
        </>
    );
}
