import { Head, Link, router } from '@inertiajs/react';
import { type ColumnDef } from '@tanstack/react-table';
import { format, isValid, parseISO } from 'date-fns';
import {
    Building2,
    Check,
    Copy,
    ImageIcon,
    Network,
    Package,
    Pencil,
    Plus,
    Trash2,
    TriangleAlert,
} from 'lucide-react';
import { useMemo, useState, type ReactNode } from 'react';
import BranchInventoryController from '@/actions/App/Http/Controllers/Platform/BranchInventoryController';
import CategoryController from '@/actions/App/Http/Controllers/Platform/CategoryController';
import ProductController from '@/actions/App/Http/Controllers/Platform/ProductController';
import ProductVariantController from '@/actions/App/Http/Controllers/Platform/ProductVariantController';
import { ConfirmDeleteDialog } from '@/components/admin/confirm-delete-dialog';
import { COLOR_PICKER_CHECKERBOARD } from '@/components/color-picker/color-swatch-popover';
import {
    DataTable,
    DataTableRowActions,
    toClientDataTablePayload,
} from '@/components/data-table';
import { PRODUCT_TAG_OPTIONS } from '@/components/platform/product-form';
import { VariantForm } from '@/components/platform/variant-form';
import { DemoStatCard } from '@/components/portal/demo-stat-card';
import { ProductGallery } from '@/components/product-gallery';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Separator } from '@/components/ui/separator';
import { usePermission } from '@/hooks/use-permissions';
import {
    colorPickerStyle,
    colorPickerSummary,
    isTransparentColorPickerValue,
    normalizeCatalogColorPickerValue,
    type ColorPickerValue,
} from '@/lib/color-picker';
import { cn } from '@/lib/utils';

type ProductVariant = {
    id: number;
    name: string;
    sku: string | null;
    attributes: Record<string, string> | null;
    status: boolean;
};

type ProductSpecificationRow = {
    label: string;
    value: string;
};

type ProductCategory = {
    id: number;
    name: string;
    slug: string;
};

type Product = {
    id: number;
    name: string;
    slug: string;
    sku: string | null;
    brand: string | null;
    description: string | null;
    tags: string[];
    specifications: ProductSpecificationRow[];
    categories: ProductCategory[];
    category_name: string;
    image: string | null;
    image_url: string | null;
    color_code?: ColorPickerValue | null;
    gallery: {
        id: number;
        path: string;
        url: string;
        sort_order: number;
    }[];
    status: boolean;
    variants: ProductVariant[];
    created_at: string | null;
    updated_at: string | null;
    created_by_name: string | null;
};

type BranchSummary = {
    branches: number;
    enabled: number;
    low_stock: number;
    missing_price: number;
};

function tagLabel(value: string): string {
    return (
        PRODUCT_TAG_OPTIONS.find((option) => option.value === value)?.label ??
        value
    );
}

function formatTimestamp(value: string | null): string {
    if (!value) {
        return '—';
    }

    const parsed = parseISO(value);

    return isValid(parsed) ? format(parsed, 'd MMM yyyy, HH:mm') : value;
}

function attributePairs(
    attributes: Record<string, string> | null,
): { key: string; value: string }[] {
    if (!attributes) {
        return [];
    }

    return Object.entries(attributes).map(([key, value]) => ({
        key,
        value: String(value),
    }));
}

function Fact({
    label,
    value,
    action,
}: {
    label: string;
    value: ReactNode;
    action?: ReactNode;
}) {
    return (
        <div className="flex min-w-0 flex-col gap-1">
            <dt className="text-muted-foreground text-[11px] font-medium tracking-wide uppercase">
                {label}
            </dt>
            <dd className="flex items-start justify-between gap-2 text-sm font-medium">
                <span className="min-w-0 wrap-break-word">{value}</span>
                {action}
            </dd>
        </div>
    );
}

function CopyValueButton({ value, label }: { value: string; label: string }) {
    const [copied, setCopied] = useState(false);

    return (
        <Button
            type="button"
            variant="ghost"
            size="icon"
            className="size-7 shrink-0"
            aria-label={`Copy ${label}`}
            onClick={() => {
                void navigator.clipboard.writeText(value).then(() => {
                    setCopied(true);
                    window.setTimeout(() => setCopied(false), 1600);
                });
            }}
        >
            {copied ? (
                <Check className="size-3.5" />
            ) : (
                <Copy className="size-3.5" />
            )}
        </Button>
    );
}

export default function PlatformProductsShow({
    product,
    branchSummary,
}: {
    product: Product;
    branchSummary: BranchSummary;
}) {
    const { can } = usePermission();
    const [variantDialogOpen, setVariantDialogOpen] = useState(false);
    const [editingVariant, setEditingVariant] = useState<ProductVariant | null>(
        null,
    );
    const [deletingVariantId, setDeletingVariantId] = useState<number | null>(
        null,
    );
    const [processing, setProcessing] = useState(false);

    const openCreateVariant = () => {
        setEditingVariant(null);
        setVariantDialogOpen(true);
    };

    const openEditVariant = (variant: ProductVariant) => {
        setEditingVariant(variant);
        setVariantDialogOpen(true);
    };

    const closeVariantDialog = () => {
        setVariantDialogOpen(false);
        setEditingVariant(null);
    };

    const canManageCatalog = can('manage-catalog');
    const canManageBranches = can('manage-branch-inventories');
    const colorCode = normalizeCatalogColorPickerValue(product.color_code);
    const transparent = isTransparentColorPickerValue(colorCode);
    const categories =
        product.categories.length > 0
            ? product.categories
            : [];
    const activeVariants = product.variants.filter(
        (variant) => variant.status,
    ).length;
    const mediaCount =
        (product.image_url ? 1 : 0) + product.gallery.length;

    const variantColumns = useMemo<ColumnDef<ProductVariant>[]>(() => {
        const columns: ColumnDef<ProductVariant>[] = [
            {
                accessorKey: 'name',
                header: 'Name',
                cell: ({ row }) => (
                    <span className="font-medium">{row.original.name}</span>
                ),
            },
            {
                accessorKey: 'sku',
                header: 'SKU',
                cell: ({ row }) => (
                    <span className="font-mono text-xs">
                        {row.original.sku ?? '—'}
                    </span>
                ),
            },
            {
                accessorKey: 'attributes',
                header: 'Attributes',
                cell: ({ row }) => {
                    const pairs = attributePairs(row.original.attributes);

                    if (pairs.length === 0) {
                        return '—';
                    }

                    return (
                        <span className="flex flex-wrap gap-1">
                            {pairs.map((pair) => (
                                <Badge
                                    key={`${pair.key}-${pair.value}`}
                                    variant="outline"
                                    className="font-normal"
                                >
                                    {pair.key}: {pair.value}
                                </Badge>
                            ))}
                        </span>
                    );
                },
            },
            {
                accessorKey: 'status',
                header: 'Status',
                cell: ({ row }) => (
                    <Badge
                        variant={
                            row.original.status ? 'default' : 'secondary'
                        }
                    >
                        {row.original.status ? 'Active' : 'Inactive'}
                    </Badge>
                ),
            },
        ];

        if (canManageCatalog) {
            columns.push({
                id: 'actions',
                header: 'Actions',
                cell: ({ row }) => (
                    <div className="flex justify-end">
                        <DataTableRowActions
                            items={[
                                {
                                    label: 'Edit',
                                    icon: Pencil,
                                    onSelect: () =>
                                        openEditVariant(row.original),
                                },
                                {
                                    label: 'Delete',
                                    icon: Trash2,
                                    destructive: true,
                                    separatorBefore: true,
                                    onSelect: () =>
                                        setDeletingVariantId(row.original.id),
                                },
                            ]}
                        />
                    </div>
                ),
            });
        }

        return columns;
    }, [canManageCatalog]);

    const variantsPayload = useMemo(
        () => toClientDataTablePayload(product.variants),
        [product.variants],
    );

    return (
        <>
            <Head title={product.name} />

            <div className="flex w-full flex-col gap-6 p-2 md:p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="min-w-0 space-y-3">
                        <p className="text-muted-foreground text-xs font-medium tracking-wide uppercase">
                            Catalog product
                        </p>
                        <div className="space-y-2">
                            <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">
                                {product.name}
                            </h1>
                            <div className="flex flex-wrap items-center gap-1.5">
                                <Badge
                                    variant={
                                        product.status ? 'default' : 'secondary'
                                    }
                                >
                                    {product.status
                                        ? 'Active in catalog'
                                        : 'Inactive'}
                                </Badge>
                                {product.brand ? (
                                    <Badge variant="outline">
                                        {product.brand}
                                    </Badge>
                                ) : null}
                                {product.sku ? (
                                    <Badge
                                        variant="outline"
                                        className="font-mono font-normal"
                                    >
                                        {product.sku}
                                    </Badge>
                                ) : null}
                                {product.tags.map((tag) => (
                                    <Badge key={tag} variant="secondary">
                                        {tagLabel(tag)}
                                    </Badge>
                                ))}
                            </div>
                        </div>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button variant="outline" asChild>
                            <Link href={ProductController.index.url()}>
                                All products
                            </Link>
                        </Button>
                        {canManageBranches ? (
                            <Button variant="outline" asChild>
                                <Link
                                    href={BranchInventoryController.forProduct.url(
                                        product.slug,
                                    )}
                                >
                                    <Network className="size-4" />
                                    Manage branches
                                </Link>
                            </Button>
                        ) : null}
                        {canManageCatalog ? (
                            <Button asChild>
                                <Link
                                    href={ProductController.edit.url(
                                        product.slug,
                                    )}
                                >
                                    <Pencil className="size-4" />
                                    Edit
                                </Link>
                            </Button>
                        ) : null}
                    </div>
                </div>

                <div className="grid items-start gap-6 xl:grid-cols-[minmax(0,1.15fr)_minmax(0,0.85fr)]">
                    <ProductGallery
                        layout="hero"
                        productName={product.name}
                        primaryUrl={product.image_url}
                        images={product.gallery}
                        colorCode={product.color_code}
                    />

                    <Card className="shadow-sm">
                        <CardHeader>
                            <CardTitle>Listing details</CardTitle>
                            <CardDescription>
                                What customers and operators see for this SKU
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-5">
                            <p className="text-sm leading-relaxed">
                                {product.description?.trim() || (
                                    <span className="text-muted-foreground">
                                        No description yet. Add one from Edit so
                                        the storefront has copy for this
                                        product.
                                    </span>
                                )}
                            </p>

                            <Separator />

                            <dl className="grid gap-4 sm:grid-cols-2">
                                <Fact
                                    label="SKU"
                                    value={
                                        <span className="font-mono">
                                            {product.sku ?? '—'}
                                        </span>
                                    }
                                    action={
                                        product.sku ? (
                                            <CopyValueButton
                                                value={product.sku}
                                                label="SKU"
                                            />
                                        ) : null
                                    }
                                />
                                <Fact
                                    label="Slug"
                                    value={
                                        <span className="font-mono">
                                            /{product.slug}
                                        </span>
                                    }
                                    action={
                                        <CopyValueButton
                                            value={product.slug}
                                            label="slug"
                                        />
                                    }
                                />
                                <Fact
                                    label="Brand"
                                    value={product.brand ?? '—'}
                                />
                                <Fact
                                    label="Catalog ID"
                                    value={
                                        <span className="font-mono">
                                            #{product.id}
                                        </span>
                                    }
                                />
                            </dl>

                            <div className="space-y-2">
                                <p className="text-muted-foreground text-[11px] font-medium tracking-wide uppercase">
                                    Categories
                                </p>
                                {categories.length > 0 ? (
                                    <div className="flex flex-wrap gap-1.5">
                                        {categories.map((category) => (
                                            <Badge
                                                key={category.id}
                                                variant="secondary"
                                                asChild
                                            >
                                                <Link
                                                    href={CategoryController.show.url(
                                                        category.slug,
                                                    )}
                                                >
                                                    {category.name}
                                                </Link>
                                            </Badge>
                                        ))}
                                    </div>
                                ) : (
                                    <p className="text-sm">{product.category_name}</p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <p className="text-muted-foreground text-[11px] font-medium tracking-wide uppercase">
                                    Merchandising tags
                                </p>
                                {product.tags.length > 0 ? (
                                    <div className="flex flex-wrap gap-1.5">
                                        {product.tags.map((tag) => (
                                            <Badge
                                                key={tag}
                                                variant="outline"
                                            >
                                                {tagLabel(tag)}
                                            </Badge>
                                        ))}
                                    </div>
                                ) : (
                                    <p className="text-muted-foreground text-sm">
                                        No storefront tags
                                    </p>
                                )}
                            </div>

                            <div className="flex items-center justify-between gap-3 rounded-xl border px-3 py-3">
                                <div className="min-w-0">
                                    <p className="text-sm font-medium">
                                        Image background
                                    </p>
                                    <p className="text-muted-foreground truncate text-xs">
                                        {colorPickerSummary(colorCode)}
                                    </p>
                                </div>
                                <span
                                    className={cn(
                                        'size-10 shrink-0 rounded-lg border border-border/70 shadow-inner',
                                        transparent &&
                                            COLOR_PICKER_CHECKERBOARD,
                                    )}
                                    style={
                                        transparent
                                            ? undefined
                                            : colorPickerStyle(colorCode)
                                    }
                                    aria-hidden
                                />
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <DemoStatCard
                        icon={Package}
                        label="Variants"
                        value={String(product.variants.length)}
                        hint={`${activeVariants} active purchasable SKU${activeVariants === 1 ? '' : 's'}`}
                    />
                    <DemoStatCard
                        icon={ImageIcon}
                        label="Media"
                        value={String(mediaCount)}
                        hint={
                            product.image_url
                                ? 'Includes primary listing photo'
                                : 'Primary photo missing'
                        }
                    />
                    <DemoStatCard
                        icon={Building2}
                        label="Branch enablement"
                        value={`${branchSummary.enabled}/${branchSummary.branches}`}
                        hint="Enabled of all branches"
                    />
                    <DemoStatCard
                        icon={TriangleAlert}
                        label="Attention"
                        value={String(
                            branchSummary.low_stock +
                                branchSummary.missing_price,
                        )}
                        hint={`${branchSummary.low_stock} low stock · ${branchSummary.missing_price} missing price`}
                        valueClassName={
                            branchSummary.low_stock +
                                branchSummary.missing_price >
                            0
                                ? 'text-destructive'
                                : undefined
                        }
                    />
                </div>

                <div className="grid items-start gap-6 xl:grid-cols-2">
                    <Card className="shadow-sm">
                        <CardHeader>
                            <CardTitle>Specifications</CardTitle>
                            <CardDescription>
                                Storefront facts shown with this listing
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {product.specifications.length > 0 ? (
                                <dl className="divide-border overflow-hidden rounded-xl border">
                                    {product.specifications.map((row) => (
                                        <div
                                            key={`${row.label}-${row.value}`}
                                            className="grid gap-1 px-4 py-3 sm:grid-cols-[minmax(0,0.4fr)_minmax(0,0.6fr)] sm:gap-4"
                                        >
                                            <dt className="text-muted-foreground text-sm">
                                                {row.label}
                                            </dt>
                                            <dd className="text-sm font-medium">
                                                {row.value}
                                            </dd>
                                        </div>
                                    ))}
                                </dl>
                            ) : (
                                <p className="text-muted-foreground text-sm">
                                    No specifications yet. Add label/value rows
                                    from Edit for nicotine, pack size, or
                                    resistance.
                                </p>
                            )}
                        </CardContent>
                    </Card>

                    <Card className="shadow-sm">
                        <CardHeader>
                            <CardTitle>Catalog record</CardTitle>
                            <CardDescription>
                                Internal metadata for this product
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <dl className="grid gap-4 sm:grid-cols-2">
                                <Fact
                                    label="Created"
                                    value={formatTimestamp(product.created_at)}
                                />
                                <Fact
                                    label="Updated"
                                    value={formatTimestamp(product.updated_at)}
                                />
                                <Fact
                                    label="Created by"
                                    value={product.created_by_name ?? '—'}
                                />
                                <Fact
                                    label="Status"
                                    value={
                                        product.status ? 'Active' : 'Inactive'
                                    }
                                />
                            </dl>
                        </CardContent>
                    </Card>
                </div>

                <Card className="shadow-sm">
                    <CardHeader className="flex flex-row flex-wrap items-start justify-between gap-3">
                        <div className="space-y-1.5">
                            <CardTitle>Branch network</CardTitle>
                            <CardDescription>
                                Catalog products stay disabled per branch until
                                you enable them and set stock or price.
                            </CardDescription>
                        </div>
                        {canManageBranches ? (
                            <Button variant="outline" size="sm" asChild>
                                <Link
                                    href={BranchInventoryController.forProduct.url(
                                        product.slug,
                                    )}
                                >
                                    <Network className="size-4" />
                                    Open branch list
                                </Link>
                            </Button>
                        ) : null}
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-3 sm:grid-cols-3">
                            <div className="rounded-xl border px-4 py-3">
                                <p className="text-muted-foreground text-xs tracking-wide uppercase">
                                    Enabled
                                </p>
                                <p className="mt-1 text-lg font-semibold tabular-nums">
                                    {branchSummary.enabled}
                                </p>
                            </div>
                            <div className="rounded-xl border px-4 py-3">
                                <p className="text-muted-foreground text-xs tracking-wide uppercase">
                                    Low stock
                                </p>
                                <p className="mt-1 text-lg font-semibold tabular-nums">
                                    {branchSummary.low_stock}
                                </p>
                            </div>
                            <div className="rounded-xl border px-4 py-3">
                                <p className="text-muted-foreground text-xs tracking-wide uppercase">
                                    Missing price
                                </p>
                                <p className="mt-1 text-lg font-semibold tabular-nums">
                                    {branchSummary.missing_price}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card className="shadow-sm">
                    <CardHeader className="flex flex-row flex-wrap items-center justify-between gap-2">
                        <div className="flex flex-col gap-1.5">
                            <CardTitle>Variants</CardTitle>
                            <CardDescription>
                                Purchasable SKUs and attribute combinations
                            </CardDescription>
                        </div>
                        {canManageCatalog ? (
                            <Button size="sm" onClick={openCreateVariant}>
                                <Plus className="size-4" />
                                Add variant
                            </Button>
                        ) : null}
                    </CardHeader>
                    <CardContent>
                        <DataTable
                            columns={variantColumns}
                            payload={variantsPayload}
                            perPage={variantsPayload.per_page}
                            onPerPageChange={() => undefined}
                            showPagination={false}
                            emptyMessage="No variants yet. Add one to define purchasable SKUs."
                        />
                    </CardContent>
                </Card>
            </div>

            <Dialog
                open={variantDialogOpen}
                onOpenChange={(open) => {
                    setVariantDialogOpen(open);

                    if (!open) {
                        setEditingVariant(null);
                    }
                }}
            >
                <DialogContent className="sm:max-w-lg">
                    <DialogHeader>
                        <DialogTitle>
                            {editingVariant ? 'Edit variant' : 'Add variant'}
                        </DialogTitle>
                        <DialogDescription>
                            Variants define purchasable SKUs for {product.name}.
                        </DialogDescription>
                    </DialogHeader>
                    <VariantForm
                        action={
                            editingVariant
                                ? ProductVariantController.update.form({
                                      product: product.slug,
                                      variant: editingVariant.id,
                                  })
                                : ProductVariantController.store.form(
                                      product.slug,
                                  )
                        }
                        isEdit={editingVariant !== null}
                        defaults={
                            editingVariant
                                ? {
                                      name: editingVariant.name,
                                      sku: editingVariant.sku,
                                      attributes: editingVariant.attributes,
                                      status: editingVariant.status,
                                  }
                                : undefined
                        }
                        onCancel={closeVariantDialog}
                        onSuccess={closeVariantDialog}
                    />
                </DialogContent>
            </Dialog>

            <ConfirmDeleteDialog
                open={deletingVariantId !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setDeletingVariantId(null);
                    }
                }}
                description="This will soft-delete the variant."
                processing={processing}
                onConfirm={() => {
                    if (deletingVariantId === null) {
                        return;
                    }

                    setProcessing(true);
                    router.delete(
                        ProductVariantController.destroy.url({
                            product: product.slug,
                            variant: deletingVariantId,
                        }),
                        {
                            preserveScroll: true,
                            onFinish: () => {
                                setProcessing(false);
                                setDeletingVariantId(null);
                            },
                        },
                    );
                }}
            />
        </>
    );
}

PlatformProductsShow.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Products', href: '/platform/products' },
        { title: 'Show', href: '#' },
    ],
};
