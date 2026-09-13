import { Head, Link, router } from '@inertiajs/react';
import { Eye, Pencil, Trash2 } from 'lucide-react';
import { useState } from 'react';
import ProductController from '@/actions/App/Http/Controllers/Platform/ProductController';
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

type ProductRow = {
    id: number;
    name: string;
    slug: string;
    sku: string | null;
    category_name: string;
    image: string | null;
    image_url: string | null;
    status: boolean;
    variants_count: number;
};

type PaginatedProducts = {
    data: ProductRow[];
    links: {
        url: string | null;
        label: string;
        active: boolean;
    }[];
};

type StatusFilter = 'all' | 'active' | 'inactive';

export default function PlatformProductsIndex({
    products,
    filters,
}: {
    products: PaginatedProducts;
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
            ProductController.index.url({
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
            <Head title="Products" />

            <div className="flex flex-col gap-6 p-2">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Products"
                        description="Manage platform catalog products"
                    />
                    {can('manage-catalog') ? (
                        <Button asChild>
                            <Link href={ProductController.create.url()}>
                                Add product
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
                            placeholder="Search products"
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
                                <TableHead>SKU</TableHead>
                                <TableHead>Category</TableHead>
                                <TableHead>Image</TableHead>
                                <TableHead>Variants</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="text-right">
                                    Actions
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {products.data.length === 0 ? (
                                <TableRow>
                                    <TableCell
                                        colSpan={8}
                                        className="text-muted-foreground py-10 text-center"
                                    >
                                        No products found.
                                    </TableCell>
                                </TableRow>
                            ) : (
                                products.data.map((product) => (
                                    <TableRow key={product.id}>
                                        <TableCell className="font-medium">
                                            {product.name}
                                        </TableCell>
                                        <TableCell>{product.slug}</TableCell>
                                        <TableCell>
                                            {product.sku ?? '—'}
                                        </TableCell>
                                        <TableCell>
                                            {product.category_name}
                                        </TableCell>
                                        <TableCell>
                                            <Avatar className="size-8 rounded-md">
                                                {product.image_url ? (
                                                    <AvatarImage
                                                        src={product.image_url}
                                                        alt=""
                                                    />
                                                ) : null}
                                                <AvatarFallback className="rounded-md text-xs">
                                                    {product.name
                                                        .slice(0, 2)
                                                        .toUpperCase()}
                                                </AvatarFallback>
                                            </Avatar>
                                        </TableCell>
                                        <TableCell>
                                            {product.variants_count}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={
                                                    product.status
                                                        ? 'default'
                                                        : 'secondary'
                                                }
                                            >
                                                {product.status
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
                                                        href={ProductController.show.url(
                                                            product.slug,
                                                        )}
                                                        aria-label={`View ${product.name}`}
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
                                                                href={ProductController.edit.url(
                                                                    product.slug,
                                                                )}
                                                                aria-label={`Edit ${product.name}`}
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
                                                            aria-label={`Delete ${product.name}`}
                                                            onClick={() =>
                                                                setDeletingSlug(
                                                                    product.slug,
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

                <SimplePagination links={products.links} />
            </div>

            <ConfirmDeleteDialog
                open={deletingSlug !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setDeletingSlug(null);
                    }
                }}
                description="This will soft-delete the product and its variants."
                processing={processing}
                onConfirm={() => {
                    if (deletingSlug === null) {
                        return;
                    }

                    setProcessing(true);
                    router.delete(ProductController.destroy.url(deletingSlug), {
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

PlatformProductsIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Products', href: '/platform/products' },
    ],
};
