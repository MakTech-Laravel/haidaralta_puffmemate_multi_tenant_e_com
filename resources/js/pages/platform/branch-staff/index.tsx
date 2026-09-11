import { Head, Link, router } from '@inertiajs/react';
import { Eye, Pencil, Trash2 } from 'lucide-react';
import { useState } from 'react';
import BranchStaffController from '@/actions/App/Http/Controllers/Platform/BranchStaffController';
import { ConfirmDeleteDialog } from '@/components/admin/confirm-delete-dialog';
import Heading from '@/components/heading';
import { SimplePagination } from '@/components/simple-pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
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
    phone: string;
    email: string | null;
    is_active: boolean;
    branch: { id: number; name: string; slug: string } | null;
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

export default function PlatformBranchStaffIndex({
    staff,
    branches,
    filters,
}: {
    staff: PaginatedStaff;
    branches: { id: number; name: string; slug: string }[];
    filters: { branch_id: number | null; search: string };
}) {
    const [search, setSearch] = useState(filters.search);
    const [deletingId, setDeletingId] = useState<number | null>(null);
    const [processing, setProcessing] = useState(false);

    const queryFor = (overrides: {
        branch_id?: string | null;
        search?: string;
    }) => {
        const branchId =
            overrides.branch_id !== undefined
                ? overrides.branch_id
                : filters.branch_id
                  ? String(filters.branch_id)
                  : null;
        const nextSearch =
            overrides.search !== undefined ? overrides.search : search;

        return {
            ...(branchId ? { branch_id: branchId } : {}),
            ...(nextSearch ? { search: nextSearch } : {}),
        };
    };

    return (
        <>
            <Head title="Branch staff" />

            <div className="flex flex-col gap-6 p-2">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Branch staff"
                        description="Manage staff across all branches from the platform"
                    />
                    <Button asChild>
                        <Link href={BranchStaffController.create.url()}>
                            Add staff
                        </Link>
                    </Button>
                </div>

                <div className="flex flex-wrap items-end gap-3">
                    <form
                        className="flex max-w-md flex-1 gap-2"
                        onSubmit={(event) => {
                            event.preventDefault();
                            router.get(
                                BranchStaffController.index.url({
                                    query: queryFor({ search }),
                                }),
                                {},
                                { preserveState: true },
                            );
                        }}
                    >
                        <Input
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Search by name or phone"
                        />
                        <Button type="submit" variant="secondary">
                            Search
                        </Button>
                    </form>

                    <div className="max-w-xs min-w-48">
                        <Select
                            value={
                                filters.branch_id
                                    ? String(filters.branch_id)
                                    : 'all'
                            }
                            onValueChange={(value) => {
                                router.get(
                                    BranchStaffController.index.url({
                                        query: queryFor({
                                            branch_id:
                                                value === 'all' ? null : value,
                                            search: filters.search,
                                        }),
                                    }),
                                    {},
                                    { preserveState: true },
                                );
                            }}
                        >
                            <SelectTrigger className="w-full">
                                <SelectValue placeholder="Filter by branch" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectGroup>
                                    <SelectItem value="all">
                                        All branches
                                    </SelectItem>
                                    {branches.map((branch) => (
                                        <SelectItem
                                            key={branch.id}
                                            value={String(branch.id)}
                                        >
                                            {branch.name}
                                        </SelectItem>
                                    ))}
                                </SelectGroup>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <div className="overflow-hidden rounded-xl border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Name</TableHead>
                                <TableHead>Phone</TableHead>
                                <TableHead>Branch</TableHead>
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
                                        colSpan={6}
                                        className="text-muted-foreground py-10 text-center"
                                    >
                                        No branch staff found.
                                    </TableCell>
                                </TableRow>
                            ) : (
                                staff.data.map((member) => (
                                    <TableRow key={member.id}>
                                        <TableCell className="font-medium">
                                            {member.name}
                                        </TableCell>
                                        <TableCell>{member.phone}</TableCell>
                                        <TableCell>
                                            {member.branch?.name ?? '—'}
                                        </TableCell>
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
                                            <div className="flex justify-end gap-1">
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    asChild
                                                >
                                                    <Link
                                                        href={BranchStaffController.show.url(
                                                            member.id,
                                                        )}
                                                        aria-label={`View ${member.name}`}
                                                    >
                                                        <Eye className="size-4" />
                                                        <span className="sr-only">
                                                            View
                                                        </span>
                                                    </Link>
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    asChild
                                                >
                                                    <Link
                                                        href={BranchStaffController.edit.url(
                                                            member.id,
                                                        )}
                                                        aria-label={`Edit ${member.name}`}
                                                    >
                                                        <Pencil className="size-4" />
                                                        <span className="sr-only">
                                                            Edit
                                                        </span>
                                                    </Link>
                                                </Button>
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="sm"
                                                    aria-label={`Delete ${member.name}`}
                                                    onClick={() =>
                                                        setDeletingId(member.id)
                                                    }
                                                >
                                                    <Trash2 className="text-destructive size-4" />
                                                    <span className="sr-only">
                                                        Delete
                                                    </span>
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
                        BranchStaffController.destroy.url(deletingId),
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

PlatformBranchStaffIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Branch staff', href: '/platform/branch-staff' },
    ],
};
