import { Head, Link, router } from '@inertiajs/react';
import { Eye, Pencil, Trash2 } from 'lucide-react';
import { useState } from 'react';
import CategoryController from '@/actions/App/Http/Controllers/Platform/CategoryController';
import { ConfirmDeleteDialog } from '@/components/admin/confirm-delete-dialog';
import Heading from '@/components/heading';
import { SimplePagination } from '@/components/simple-pagination';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
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
import { usePermission } from '@/hooks/use-permissions';

type CategoryRow = {
    id: number;
    name: string;
    slug: string;
    parent_name: string | null;
    image: string | null;
    image_url: string | null;
    status: boolean;
    products_count: number;
};

type PaginatedCategories = {
    data: CategoryRow[];
    links: {
        url: string | null;
        label: string;
        active: boolean;
    }[];
};

type StatusFilter = 'all' | 'active' | 'inactive';

export default function PlatformCategoriesIndex({
    categories,
    filters,
}: {
    categories: PaginatedCategories;
    filters: { search: string; status: StatusFilter };
}) {
    const { can } = usePermission();
    const [search, setSearch] = useState(filters.search);
    const [status, setStatus] = useState<StatusFilter>(filters.status);
    const [deletingSlug, setDeletingSlug] = useState<string | null>(null);
    const [processing, setProcessing] = useState(false);

    const applyFilters = (next: {
        search?: string;
        status?: StatusFilter;
    }) => {
        const nextSearch = next.search ?? search;
        const nextStatus = next.status ?? status;

        router.get(
            CategoryController.index.url({
                query: {
                    search: nextSearch || undefined,
                    status: nextStatus === 'all' ? undefined : nextStatus,
                },
            }),
            {},
            { preserveState: true },
        );
    };

    return (
        <>
            <Head title="Categories" />

            <div className="flex flex-col gap-6 p-2">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Categories"
                        description="Organize the platform product catalog"
                    />
                    {can('manage-catalog') ? (
                        <Button asChild>
                            <Link href={CategoryController.create.url()}>
                                Add category
                            </Link>
                        </Button>
                    ) : null}
                </div>

                <div className="flex flex-wrap items-end gap-3">
                    <form
                        className="flex max-w-md flex-1 gap-2"
                        onSubmit={(event) => {
                            event.preventDefault();
                            applyFilters({ search });
                        }}
                    >
                        <Input
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Search categories"
                        />
                        <Button type="submit" variant="secondary">
                            Search
                        </Button>
                    </form>

                    <div className="w-44">
                        <Select
                            value={status}
                            onValueChange={(value) => {
                                const next = value as StatusFilter;
                                setStatus(next);
                                applyFilters({ status: next });
                            }}
                        >
                            <SelectTrigger aria-label="Filter by status">
                                <SelectValue placeholder="Status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectGroup>
                                    <SelectItem value="all">
                                        All statuses
                                    </SelectItem>
                                    <SelectItem value="active">
                                        Active
                                    </SelectItem>
                                    <SelectItem value="inactive">
                                        Inactive
                                    </SelectItem>
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
                                <TableHead>Slug</TableHead>
                                <TableHead>Parent</TableHead>
                                <TableHead>Image</TableHead>
                                <TableHead>Products</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="text-right">
                                    Actions
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {categories.data.length === 0 ? (
                                <TableRow>
                                    <TableCell
                                        colSpan={7}
                                        className="text-muted-foreground py-10 text-center"
                                    >
                                        No categories found.
                                    </TableCell>
                                </TableRow>
                            ) : (
                                categories.data.map((category) => (
                                    <TableRow key={category.id}>
                                        <TableCell className="font-medium">
                                            {category.name}
                                        </TableCell>
                                        <TableCell>{category.slug}</TableCell>
                                        <TableCell>
                                            {category.parent_name ?? '—'}
                                        </TableCell>
                                        <TableCell>
                                            <Avatar className="size-8 rounded-md">
                                                {category.image_url ? (
                                                    <AvatarImage
                                                        src={
                                                            category.image_url
                                                        }
                                                        alt=""
                                                    />
                                                ) : null}
                                                <AvatarFallback className="rounded-md text-xs">
                                                    {category.name
                                                        .slice(0, 2)
                                                        .toUpperCase()}
                                                </AvatarFallback>
                                            </Avatar>
                                        </TableCell>
                                        <TableCell>
                                            {category.products_count}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={
                                                    category.status
                                                        ? 'default'
                                                        : 'secondary'
                                                }
                                            >
                                                {category.status
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
                                                        href={CategoryController.show.url(
                                                            category.slug,
                                                        )}
                                                        aria-label={`View ${category.name}`}
                                                    >
                                                        <Eye className="size-4" />
                                                        <span className="sr-only">
                                                            View
                                                        </span>
                                                    </Link>
                                                </Button>
                                                {can('manage-catalog') ? (
                                                    <>
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            asChild
                                                        >
                                                            <Link
                                                                href={CategoryController.edit.url(
                                                                    category.slug,
                                                                )}
                                                                aria-label={`Edit ${category.name}`}
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
                                                            aria-label={`Delete ${category.name}`}
                                                            onClick={() =>
                                                                setDeletingSlug(
                                                                    category.slug,
                                                                )
                                                            }
                                                        >
                                                            <Trash2 className="text-destructive size-4" />
                                                            <span className="sr-only">
                                                                Delete
                                                            </span>
                                                        </Button>
                                                    </>
                                                ) : null}
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                </div>

                <SimplePagination links={categories.links} />
            </div>

            <ConfirmDeleteDialog
                open={deletingSlug !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setDeletingSlug(null);
                    }
                }}
                description="This will soft-delete the category. Categories with products cannot be deleted."
                processing={processing}
                onConfirm={() => {
                    if (deletingSlug === null) {
                        return;
                    }

                    setProcessing(true);
                    router.delete(
                        CategoryController.destroy.url(deletingSlug),
                        {
                            preserveScroll: true,
                            onFinish: () => {
                                setProcessing(false);
                                setDeletingSlug(null);
                            },
                        },
                    );
                }}
            />
        </>
    );
}

PlatformCategoriesIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Categories', href: '/platform/categories' },
    ],
};
