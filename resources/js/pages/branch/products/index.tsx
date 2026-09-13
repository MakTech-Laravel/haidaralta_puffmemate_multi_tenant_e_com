import { Head, Link, router, setLayoutProps } from '@inertiajs/react';
import { useState } from 'react';
import ProductController from '@/actions/App/Http/Controllers/Branch/ProductController';
import Heading from '@/components/heading';
import { SimplePagination } from '@/components/simple-pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

type ProductRow = {
    id: number;
    name: string;
    slug: string;
    sku: string | null;
    category: { id: number; name: string; slug: string } | null;
    status: boolean;
    enabled: boolean;
    quantity: number;
    available_quantity: number;
    minimum_stock: number;
    is_low_stock: boolean;
    selling_price: string | number | null;
    image_url: string | null;
};

type PaginatedProducts = {
    data: ProductRow[];
    links: { url: string | null; label: string; active: boolean }[];
};

type Branch = { id: number; name: string; slug: string };

export default function BranchProductsIndex({
    products,
    branch,
    filters,
}: {
    products: PaginatedProducts;
    branch: Branch;
    filters: { search: string };
}) {
    const [search, setSearch] = useState(filters.search);
    const [togglingSlug, setTogglingSlug] = useState<string | null>(null);

    setLayoutProps({
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: `/branch/${branch.slug}/dashboard`,
            },
            { title: 'Products', href: ProductController.index.url(branch.slug) },
        ],
    });

    const toggleEnabled = (product: ProductRow, status: boolean) => {
        setTogglingSlug(product.slug);
        router.put(
            ProductController.toggle.url({
                branch: branch.slug,
                product: product.slug,
            }),
            { status },
            {
                preserveScroll: true,
                onFinish: () => setTogglingSlug(null),
            },
        );
    };

    return (
        <>
            <Head title="Products" />

            <div className="space-y-6 p-2">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Products"
                        description={`Enable catalog products and manage stock for ${branch.name}`}
                    />
                    <Button asChild>
                        <Link href={ProductController.create.url(branch.slug)}>
                            Add product
                        </Link>
                    </Button>
                </div>

                <form
                    className="flex max-w-md gap-2"
                    onSubmit={(event) => {
                        event.preventDefault();
                        router.get(
                            ProductController.index.url(branch.slug, {
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
                        placeholder="Search products…"
                    />
                    <Button type="submit" variant="secondary">
                        Search
                    </Button>
                </form>

                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Product</TableHead>
                            <TableHead>SKU</TableHead>
                            <TableHead>Stock</TableHead>
                            <TableHead>Price</TableHead>
                            <TableHead>Enabled</TableHead>
                            <TableHead className="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {products.data.length === 0 ? (
                            <TableRow>
                                <TableCell
                                    colSpan={6}
                                    className="text-muted-foreground text-center"
                                >
                                    No products found.
                                </TableCell>
                            </TableRow>
                        ) : (
                            products.data.map((product) => (
                                <TableRow key={product.id}>
                                    <TableCell>
                                        <div className="font-medium">
                                            {product.name}
                                        </div>
                                        <div className="text-muted-foreground text-xs">
                                            {product.category?.name ?? '—'}
                                        </div>
                                    </TableCell>
                                    <TableCell>{product.sku ?? '—'}</TableCell>
                                    <TableCell>
                                        <div className="flex items-center gap-2">
                                            <span>
                                                {product.available_quantity}
                                            </span>
                                            {product.is_low_stock ? (
                                                <Badge variant="destructive">
                                                    Low
                                                </Badge>
                                            ) : null}
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        {product.selling_price ?? '—'}
                                    </TableCell>
                                    <TableCell>
                                        <Checkbox
                                            checked={product.enabled}
                                            disabled={
                                                togglingSlug === product.slug
                                            }
                                            onCheckedChange={(checked) =>
                                                toggleEnabled(
                                                    product,
                                                    checked === true,
                                                )
                                            }
                                        />
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <Button variant="outline" size="sm" asChild>
                                            <Link
                                                href={ProductController.show.url(
                                                    {
                                                        branch: branch.slug,
                                                        product: product.slug,
                                                    },
                                                )}
                                            >
                                                Manage
                                            </Link>
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            ))
                        )}
                    </TableBody>
                </Table>

                <SimplePagination links={products.links} />
            </div>
        </>
    );
}
