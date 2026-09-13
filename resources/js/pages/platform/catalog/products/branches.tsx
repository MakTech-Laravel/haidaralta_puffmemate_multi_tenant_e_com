import { Head, Link, router, setLayoutProps } from '@inertiajs/react';
import {
    AlertTriangle,
    Building2,
    CircleDollarSign,
    PackageCheck,
    Search,
    Settings2,
    Store,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import BranchInventoryController from '@/actions/App/Http/Controllers/Platform/BranchInventoryController';
import ProductController from '@/actions/App/Http/Controllers/Platform/ProductController';
import Heading from '@/components/heading';
import { DemoStatCard } from '@/components/portal/demo-stat-card';
import { COLOR_PICKER_CHECKERBOARD } from '@/components/color-picker/color-swatch-popover';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
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
import {
    colorPickerStyle,
    isTransparentColorPickerValue,
    normalizeCatalogColorPickerValue,
    type ColorPickerValue,
} from '@/lib/color-picker';
import { cn } from '@/lib/utils';

type BranchInventoryRow = {
    id: number;
    name: string;
    slug: string;
    code: string | null;
    branch_active: boolean;
    enabled: boolean;
    inventory: {
        quantity: number;
        available_quantity: number;
        minimum_stock: number;
        is_low_stock: boolean;
    } | null;
    price: {
        selling_price: string;
        discount_type: 'none' | 'fixed' | 'percent';
        discount_price: string | null;
    } | null;
    manage_url: string;
};

type ProductSummary = {
    id: number;
    name: string;
    slug: string;
    sku: string | null;
    image_url: string | null;
    color_code?: ColorPickerValue | null;
    category_name: string;
    status: boolean;
};

type Summary = {
    branches: number;
    enabled: number;
    low_stock: number;
    missing_price: number;
};

type ScopeFilter =
    | 'all'
    | 'enabled'
    | 'disabled'
    | 'low_stock'
    | 'missing_price';

const SCOPE_OPTIONS: { value: ScopeFilter; label: string }[] = [
    { value: 'all', label: 'All' },
    { value: 'enabled', label: 'Enabled' },
    { value: 'disabled', label: 'Disabled' },
    { value: 'low_stock', label: 'Low stock' },
    { value: 'missing_price', label: 'Missing price' },
];

function formatPrice(row: BranchInventoryRow): string {
    if (!row.price) {
        return '—';
    }

    const base = row.price.selling_price;

    if (row.price.discount_type === 'percent' && row.price.discount_price) {
        return `${base} · ${row.price.discount_price}% off`;
    }

    if (row.price.discount_type === 'fixed' && row.price.discount_price) {
        return `${base} · sale ${row.price.discount_price}`;
    }

    return base;
}

export default function PlatformProductBranches({
    product,
    summary,
    branches,
}: {
    product: ProductSummary;
    summary: Summary;
    branches: BranchInventoryRow[];
}) {
    const [search, setSearch] = useState('');
    const [scope, setScope] = useState<ScopeFilter>('all');
    const [togglingSlug, setTogglingSlug] = useState<string | null>(null);

    const colorCode = normalizeCatalogColorPickerValue(product.color_code);
    const transparent = isTransparentColorPickerValue(colorCode);

    setLayoutProps({
        breadcrumbs: [
            { title: 'Dashboard', href: '/platform/dashboard' },
            { title: 'Products', href: ProductController.index.url() },
            {
                title: product.name,
                href: ProductController.show.url(product.slug),
            },
            {
                title: 'Branches',
                href: BranchInventoryController.forProduct.url(product.slug),
            },
        ],
    });

    const filtered = useMemo(() => {
        const needle = search.trim().toLowerCase();

        return branches.filter((row) => {
            if (scope === 'enabled' && !row.enabled) {
                return false;
            }

            if (scope === 'disabled' && row.enabled) {
                return false;
            }

            if (scope === 'low_stock' && !row.inventory?.is_low_stock) {
                return false;
            }

            if (scope === 'missing_price' && row.price !== null) {
                return false;
            }

            if (!needle) {
                return true;
            }

            return (
                row.name.toLowerCase().includes(needle) ||
                row.slug.toLowerCase().includes(needle) ||
                (row.code?.toLowerCase().includes(needle) ?? false)
            );
        });
    }, [branches, scope, search]);

    const toggleEnabled = (row: BranchInventoryRow, next: boolean) => {
        setTogglingSlug(row.slug);
        router.put(
            BranchInventoryController.toggle.url({
                branch: row.slug,
                product: product.slug,
            }),
            { status: next },
            {
                preserveScroll: true,
                onFinish: () => setTogglingSlug(null),
            },
        );
    };

    return (
        <>
            <Head title={`${product.name} · Branches`} />

            <div className="flex w-full flex-col gap-6 p-2 md:p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="flex items-start gap-4">
                        <Avatar
                            className={cn(
                                'size-14 rounded-xl border border-border/70',
                                transparent && COLOR_PICKER_CHECKERBOARD,
                            )}
                            style={
                                transparent
                                    ? undefined
                                    : colorPickerStyle(colorCode)
                            }
                        >
                            {product.image_url ? (
                                <AvatarImage
                                    src={product.image_url}
                                    alt={product.name}
                                    className="object-contain p-1"
                                />
                            ) : null}
                            <AvatarFallback className="rounded-xl bg-transparent text-base">
                                {product.name.slice(0, 2).toUpperCase()}
                            </AvatarFallback>
                        </Avatar>
                        <div className="[&_header]:mb-0">
                            <Heading
                                title="Manage branches"
                                description={`Inventory, pricing, and enablement for ${product.name}`}
                            />
                            <div className="text-muted-foreground mt-2 flex flex-wrap items-center gap-2 text-sm">
                                <span>{product.category_name || 'Uncategorized'}</span>
                                {product.sku ? (
                                    <>
                                        <span aria-hidden>·</span>
                                        <span className="font-mono text-xs">
                                            {product.sku}
                                        </span>
                                    </>
                                ) : null}
                                <Badge
                                    variant={
                                        product.status ? 'default' : 'secondary'
                                    }
                                >
                                    {product.status ? 'Active' : 'Inactive'}
                                </Badge>
                            </div>
                        </div>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button variant="outline" asChild>
                            <Link
                                href={ProductController.show.url(product.slug)}
                            >
                                Product details
                            </Link>
                        </Button>
                        <Button variant="default" asChild>
                            <Link href={ProductController.index.url()}>
                                All products
                            </Link>
                        </Button>
                    </div>
                </div>

                <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <DemoStatCard
                        icon={Building2}
                        label="Branches"
                        value={String(summary.branches)}
                        hint="Locations in the network"
                    />
                    <DemoStatCard
                        icon={PackageCheck}
                        label="Enabled"
                        value={String(summary.enabled)}
                        hint="Selling this product"
                    />
                    <DemoStatCard
                        icon={AlertTriangle}
                        label="Low stock"
                        value={String(summary.low_stock)}
                        hint="At or below minimum"
                        valueClassName={
                            summary.low_stock > 0
                                ? 'text-destructive'
                                : undefined
                        }
                    />
                    <DemoStatCard
                        icon={CircleDollarSign}
                        label="Missing price"
                        value={String(summary.missing_price)}
                        hint="No active branch price"
                    />
                </div>

                <Card className="shadow-none">
                    <CardHeader className="gap-4 border-b border-border/60 pb-4">
                        <div className="flex flex-wrap items-start justify-between gap-3">
                            <div className="space-y-1">
                                <CardTitle className="flex items-center gap-2 text-base">
                                    <Store className="text-muted-foreground size-4" />
                                    Branch inventory
                                </CardTitle>
                                <CardDescription>
                                    Enable a branch, then open Manage to adjust
                                    stock and pricing.
                                </CardDescription>
                            </div>
                            <p className="text-muted-foreground text-xs tabular-nums">
                                {filtered.length} of {branches.length} shown
                            </p>
                        </div>
                        <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <div className="flex flex-wrap gap-1.5">
                                {SCOPE_OPTIONS.map((option) => (
                                    <Button
                                        key={option.value}
                                        type="button"
                                        size="sm"
                                        variant={
                                            scope === option.value
                                                ? 'default'
                                                : 'outline'
                                        }
                                        className="h-8"
                                        onClick={() => setScope(option.value)}
                                    >
                                        {option.label}
                                    </Button>
                                ))}
                            </div>
                            <div className="relative w-full max-w-sm">
                                <Search className="text-muted-foreground pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2" />
                                <Input
                                    value={search}
                                    onChange={(event) =>
                                        setSearch(event.target.value)
                                    }
                                    placeholder="Search branch name, slug, or code…"
                                    className="pl-9"
                                />
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow className="hover:bg-transparent">
                                    <TableHead>Branch</TableHead>
                                    <TableHead className="w-[7rem]">
                                        Enabled
                                    </TableHead>
                                    <TableHead className="w-[9rem]">
                                        On hand
                                    </TableHead>
                                    <TableHead className="w-[9rem]">
                                        Available
                                    </TableHead>
                                    <TableHead className="min-w-[10rem]">
                                        Price
                                    </TableHead>
                                    <TableHead className="w-[7rem] text-right">
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {filtered.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={6}
                                            className="text-muted-foreground h-24 text-center"
                                        >
                                            No branches match this filter.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    filtered.map((row) => (
                                        <TableRow key={row.id}>
                                            <TableCell>
                                                <div className="flex flex-col gap-1">
                                                    <div className="flex flex-wrap items-center gap-2">
                                                        <span className="font-medium">
                                                            {row.name}
                                                        </span>
                                                        {!row.branch_active ? (
                                                            <Badge variant="secondary">
                                                                Branch inactive
                                                            </Badge>
                                                        ) : null}
                                                        {row.inventory
                                                            ?.is_low_stock ? (
                                                            <Badge
                                                                variant="outline"
                                                                className="border-destructive/40 text-destructive"
                                                            >
                                                                Low stock
                                                            </Badge>
                                                        ) : null}
                                                    </div>
                                                    <div className="text-muted-foreground text-xs">
                                                        {row.code
                                                            ? `${row.code} · `
                                                            : ''}
                                                        /{row.slug}
                                                    </div>
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <Checkbox
                                                    checked={row.enabled}
                                                    disabled={
                                                        togglingSlug ===
                                                        row.slug
                                                    }
                                                    onCheckedChange={(
                                                        checked,
                                                    ) =>
                                                        toggleEnabled(
                                                            row,
                                                            checked === true,
                                                        )
                                                    }
                                                    aria-label={`Enable ${row.name}`}
                                                />
                                            </TableCell>
                                            <TableCell className="tabular-nums">
                                                {row.inventory?.quantity ?? '—'}
                                            </TableCell>
                                            <TableCell className="tabular-nums">
                                                {row.inventory
                                                    ?.available_quantity ?? '—'}
                                            </TableCell>
                                            <TableCell className="tabular-nums">
                                                {formatPrice(row)}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    asChild
                                                >
                                                    <Link href={row.manage_url}>
                                                        <Settings2 className="size-3.5" />
                                                        Manage
                                                    </Link>
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
