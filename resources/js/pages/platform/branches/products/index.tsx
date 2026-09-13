import { Head, Link, router, setLayoutProps } from '@inertiajs/react';
import { useState } from 'react';
import BranchInventoryController from '@/actions/App/Http/Controllers/Platform/BranchInventoryController';
import Heading from '@/components/heading';
import { SimplePagination } from '@/components/simple-pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
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
    category: { id: number; name: string } | null;
    enabled: boolean;
    quantity: number;
    available_quantity: number;
    minimum_stock: number;
    is_low_stock: boolean;
    selling_price: string | number | null;
};

type PaginatedProducts = {
    data: ProductRow[];
    links: { url: string | null; label: string; active: boolean }[];
};

type Branch = { id: number; name: string; slug: string };

export default function PlatformBranchProductsIndex({
    branch,
    products,
    filters,
}: {
    branch: Branch;
    products: PaginatedProducts;
    filters: { search: string };
}) {
    const [search, setSearch] = useState(filters.search);
    const [togglingSlug, setTogglingSlug] = useState<string | null>(null);

    setLayoutProps({
        breadcrumbs: [
            { title: 'Dashboard', href: '/platform/dashboard' },
            { title: 'Branches', href: '/platform/branches' },
            {
                title: branch.name,
                href: `/platform/branches/${branch.slug}`,
            },
            {
                title: 'Products',
                href: BranchInventoryController.index.url(branch.slug),
            },
        ],
    });

    return (
        <>
            <Head title={`${branch.name} products`} />

            <div className="space-y-6 p-2">
                <Heading
                    title={`${branch.name} products`}
                    description="Enablement, stock, and pricing for this branch"
                />

                <form
                    className="flex max-w-md gap-2"
                    onSubmit={(event) => {
                        event.preventDefault();
                        router.get(
                            BranchInventoryController.index.url(branch.slug, {
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
                            <TableHead>Stock</TableHead>
                            <TableHead>Price</TableHead>
                            <TableHead>Enabled</TableHead>
                            <TableHead className="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {products.data.map((product) => (
                            <TableRow key={product.id}>
                                <TableCell>
                                    <div className="font-medium">
                                        {product.name}
                                    </div>
                                    <div className="text-muted-foreground text-xs">
                                        {product.sku ?? product.slug}
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <div className="flex items-center gap-2">
                                        {product.available_quantity}
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
                                        onCheckedChange={(checked) => {
                                            setTogglingSlug(product.slug);
                                            router.put(
                                                BranchInventoryController.toggle.url(
                                                    {
                                                        branch: branch.slug,
                                                        product: product.slug,
                                                    },
                                                ),
                                                { status: checked === true },
                                                {
                                                    preserveScroll: true,
                                                    onFinish: () =>
                                                        setTogglingSlug(null),
                                                },
                                            );
                                        }}
                                    />
                                </TableCell>
                                <TableCell className="text-right">
                                    <Button variant="outline" size="sm" asChild>
                                        <Link
                                            href={BranchInventoryController.show.url(
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
                        ))}
                    </TableBody>
                </Table>

                <SimplePagination links={products.links} />
            </div>
        </>
    );
}
