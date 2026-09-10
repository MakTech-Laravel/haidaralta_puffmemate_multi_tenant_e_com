import { Head, Link, router } from '@inertiajs/react';
import { Eye, Pencil, Trash2 } from 'lucide-react';
import { useState } from 'react';
import BranchController from '@/actions/App/Http/Controllers/Platform/BranchController';
import { ConfirmDeleteDialog } from '@/components/admin/confirm-delete-dialog';
import Heading from '@/components/heading';
import { SimplePagination } from '@/components/simple-pagination';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
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
import { usePermission } from '@/hooks/use-permissions';

type BranchRow = {
    id: number;
    name: string;
    slug: string;
    code: string;
    logo: string | null;
    logo_url: string | null;
    status: boolean;
    users_count: number;
};

type PaginatedBranches = {
    data: BranchRow[];
    links: {
        url: string | null;
        label: string;
        active: boolean;
    }[];
};

export default function PlatformBranchesIndex({
    branches,
    filters,
}: {
    branches: PaginatedBranches;
    filters: { search: string };
}) {
    const { can } = usePermission();
    const [search, setSearch] = useState(filters.search);
    const [deletingSlug, setDeletingSlug] = useState<string | null>(null);
    const [processing, setProcessing] = useState(false);

    return (
        <>
            <Head title="Branches" />

            <div className="flex flex-col gap-6 p-2">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Branches"
                        description="Manage store branches and their portals"
                    />
                    {can('create-branches') ? (
                        <Button asChild>
                            <Link href={BranchController.create.url()}>
                                Add branch
                            </Link>
                        </Button>
                    ) : null}
                </div>

                <form
                    className="flex max-w-md gap-2"
                    onSubmit={(event) => {
                        event.preventDefault();
                        router.get(
                            BranchController.index.url({
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
                        placeholder="Search branches"
                    />
                    <Button type="submit" variant="secondary">
                        Search
                    </Button>
                </form>

                <div className="overflow-hidden rounded-xl border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Name</TableHead>
                                <TableHead>Code</TableHead>
                                <TableHead>Slug</TableHead>
                                <TableHead>Logo</TableHead>
                                <TableHead>Staff</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="text-right">
                                    Actions
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {branches.data.length === 0 ? (
                                <TableRow>
                                    <TableCell
                                        colSpan={7}
                                        className="text-muted-foreground py-10 text-center"
                                    >
                                        No branches found.
                                    </TableCell>
                                </TableRow>
                            ) : (
                                branches.data.map((branch) => (
                                    <TableRow key={branch.id}>
                                        <TableCell className="font-medium">
                                            {branch.name}
                                        </TableCell>
                                        <TableCell>{branch.code}</TableCell>
                                        <TableCell>{branch.slug}</TableCell>
                                        <TableCell>
                                            <Avatar className="size-8 rounded-md">
                                                {branch.logo_url ? (
                                                    <AvatarImage
                                                        src={branch.logo_url}
                                                        alt=""
                                                    />
                                                ) : null}
                                                <AvatarFallback className="rounded-md text-xs">
                                                    {branch.name
                                                        .slice(0, 2)
                                                        .toUpperCase()}
                                                </AvatarFallback>
                                            </Avatar>
                                        </TableCell>
                                        <TableCell>
                                            {branch.users_count}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={
                                                    branch.status
                                                        ? 'default'
                                                        : 'secondary'
                                                }
                                            >
                                                {branch.status
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
                                                        href={BranchController.show.url(
                                                            branch.slug,
                                                        )}
                                                        aria-label={`View ${branch.name}`}
                                                    >
                                                        <Eye className="size-4" />
                                                        <span className="sr-only">
                                                            View
                                                        </span>
                                                    </Link>
                                                </Button>
                                                {can('edit-branches') ? (
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        asChild
                                                    >
                                                        <Link
                                                            href={BranchController.edit.url(
                                                                branch.slug,
                                                            )}
                                                            aria-label={`Edit ${branch.name}`}
                                                        >
                                                            <Pencil className="size-4" />
                                                            <span className="sr-only">
                                                                Edit
                                                            </span>
                                                        </Link>
                                                    </Button>
                                                ) : null}
                                                {can('delete-branches') ? (
                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        size="sm"
                                                        aria-label={`Delete ${branch.name}`}
                                                        onClick={() =>
                                                            setDeletingSlug(
                                                                branch.slug,
                                                            )
                                                        }
                                                    >
                                                        <Trash2 className="text-destructive size-4" />
                                                        <span className="sr-only">
                                                            Delete
                                                        </span>
                                                    </Button>
                                                ) : null}
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                </div>

                <SimplePagination links={branches.links} />
            </div>

            <ConfirmDeleteDialog
                open={deletingSlug !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setDeletingSlug(null);
                    }
                }}
                description="This will permanently delete the branch. Branches with staff cannot be deleted."
                processing={processing}
                onConfirm={() => {
                    if (deletingSlug === null) {
                        return;
                    }

                    setProcessing(true);
                    router.delete(BranchController.destroy.url(deletingSlug), {
                        preserveScroll: true,
                        onFinish: () => {
                            setProcessing(false);
                            setDeletingSlug(null);
                        },
                    });
                }}
            />
        </>
    );
}

PlatformBranchesIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Branches', href: '/platform/branches' },
    ],
};
