import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import BranchController from '@/actions/App/Http/Controllers/Platform/BranchController';
import BranchScopedStaffController from '@/actions/App/Http/Controllers/Platform/BranchScopedStaffController';
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

type Branch = {
    id: number;
    name: string;
    slug: string;
    code: string;
};

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

export default function PlatformBranchScopedStaffIndex({
    branch,
    staff,
}: {
    branch: Branch;
    staff: PaginatedStaff;
}) {
    const [deletingId, setDeletingId] = useState<number | null>(null);
    const [processing, setProcessing] = useState(false);

    return (
        <>
            <Head title={`Staff · ${branch.name}`} />

            <div className="flex flex-col gap-6 p-2">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title={`${branch.name} staff`}
                        description={`Manage staff for ${branch.code}`}
                    />
                    <div className="flex gap-2">
                        <Button variant="secondary" asChild>
                            <Link href={BranchController.show.url(branch.slug)}>
                                Back to branch
                            </Link>
                        </Button>
                        <Button asChild>
                            <Link
                                href={BranchScopedStaffController.create.url(
                                    branch.slug,
                                )}
                            >
                                Add staff
                            </Link>
                        </Button>
                    </div>
                </div>

                <div className="overflow-hidden rounded-xl border">
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
                                        No staff found for this branch.
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
                                            <Badge
                                                variant={
                                                    member.is_active
                                                        ? 'default'
                                                        : 'secondary'
                                                }
                                            >
                                                {member.is_active
                                                    ? 'Active'
                                                    : 'Inactive'}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <div className="flex justify-end gap-2">
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    asChild
                                                >
                                                    <Link
                                                        href={BranchScopedStaffController.show.url(
                                                            [
                                                                branch.slug,
                                                                member.id,
                                                            ],
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
                                                        href={BranchScopedStaffController.edit.url(
                                                            [
                                                                branch.slug,
                                                                member.id,
                                                            ],
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
                                            </div>
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
                description="This will permanently delete the branch staff member."
                processing={processing}
                onConfirm={() => {
                    if (deletingId === null) {
                        return;
                    }

                    setProcessing(true);
                    router.delete(
                        BranchScopedStaffController.destroy.url([
                            branch.slug,
                            deletingId,
                        ]),
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

PlatformBranchScopedStaffIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Branches', href: '/platform/branches' },
        { title: 'Staff', href: '#' },
    ],
};
