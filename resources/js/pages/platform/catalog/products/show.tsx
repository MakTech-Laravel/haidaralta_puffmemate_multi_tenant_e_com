import { Head, Link, router } from '@inertiajs/react';
import { type ColumnDef } from '@tanstack/react-table';
import { Network, Pencil, Plus, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';
import type { ReactNode } from 'react';
import BranchInventoryController from '@/actions/App/Http/Controllers/Platform/BranchInventoryController';
import ProductController from '@/actions/App/Http/Controllers/Platform/ProductController';
import ProductVariantController from '@/actions/App/Http/Controllers/Platform/ProductVariantController';
import { ConfirmDeleteDialog } from '@/components/admin/confirm-delete-dialog';
import {
    DataTable,
    DataTableRowActions,
    toClientDataTablePayload,
} from '@/components/data-table';
import Heading from '@/components/heading';
import { PRODUCT_TAG_OPTIONS } from '@/components/platform/product-form';
import { VariantForm } from '@/components/platform/variant-form';
import { ProductGallery } from '@/components/product-gallery';
import { COLOR_PICKER_CHECKERBOARD } from '@/components/color-picker/color-swatch-popover';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
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
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { usePermission } from '@/hooks/use-permissions';
import {
    colorPickerStyle,
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

type Product = {
    id: number;
    name: string;
    slug: string;
    sku: string | null;
    brand: string | null;
    description: string | null;
    tags: string[];
    specifications: ProductSpecificationRow[];
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
};

function tagLabel(value: string): string {
    return (
        PRODUCT_TAG_OPTIONS.find((option) => option.value === value)?.label ??
        value
    );
}

function DetailItem({
    label,
    value,
}: {
    label: string;
    value: ReactNode;
}) {
    return (
        <div className="flex flex-col gap-1">
            <dt className="text-muted-foreground text-sm">{label}</dt>
            <dd className="text-sm font-medium">{value}</dd>
        </div>
    );
}

export default function PlatformProductsShow({ product }: { product: Product }) {
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
    const colorCode = normalizeCatalogColorPickerValue(product.color_code);
    const transparent = isTransparentColorPickerValue(colorCode);

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
                cell: ({ row }) => row.original.sku ?? '—',
            },
            {
                accessorKey: 'attributes',
                header: 'Attributes',
                cell: ({ row }) => (
                    <span className="max-w-xs truncate font-mono text-xs">
                        {row.original.attributes
                            ? JSON.stringify(row.original.attributes)
                            : '—'}
                    </span>
                ),
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
                                title={product.name}
                                description={`/${product.slug}${product.sku ? ` · ${product.sku}` : ''}`}
                            />
                        </div>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button variant="outline" asChild>
                            <Link href={ProductController.index.url()}>
                                Back
                            </Link>
                        </Button>
                        {can('manage-branch-inventories') ? (
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
                        {can('manage-catalog') ? (
                            <Button asChild>
                                <Link
                                    href={ProductController.edit.url(
                                        product.slug,
                                    )}
                                >
                                    Edit
                                </Link>
                            </Button>
                        ) : null}
                    </div>
                </div>

                <Alert>
                    <AlertTitle>Branch availability</AlertTitle>
                    <AlertDescription className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <span>
                            Catalog products stay disabled per branch until you
                            enable them and set stock or price.
                        </span>
                        {can('manage-branch-inventories') ? (
                            <Button
                                size="sm"
                                variant="outline"
                                className="w-fit shrink-0"
                                asChild
                            >
                                <Link
                                    href={BranchInventoryController.forProduct.url(
                                        product.slug,
                                    )}
                                >
                                    Open branch list
                                </Link>
                            </Button>
                        ) : null}
                    </AlertDescription>
                </Alert>

                <Card>
                    <CardHeader>
                        <CardTitle>Details</CardTitle>
                        <CardDescription>
                            Catalog metadata for this product
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <dl className="grid gap-5 sm:grid-cols-2">
                            <DetailItem
                                label="Status"
                                value={
                                    product.status ? 'Active' : 'Inactive'
                                }
                            />
                            <DetailItem
                                label="Categories"
                                value={product.category_name}
                            />
                            <DetailItem
                                label="SKU"
                                value={product.sku ?? '—'}
                            />
                            <DetailItem
                                label="Brand"
                                value={product.brand ?? '—'}
                            />
                            <DetailItem
                                label="Slug"
                                value={product.slug}
                            />
                            <DetailItem
                                label="Tags"
                                value={
                                    product.tags.length > 0 ? (
                                        <span className="flex flex-wrap gap-1.5">
                                            {product.tags.map((tag) => (
                                                <Badge
                                                    key={tag}
                                                    variant="secondary"
                                                >
                                                    {tagLabel(tag)}
                                                </Badge>
                                            ))}
                                        </span>
                                    ) : (
                                        '—'
                                    )
                                }
                            />
                            <DetailItem
                                label="Description"
                                value={product.description ?? '—'}
                            />
                            {product.specifications.length > 0 ? (
                                <div className="flex flex-col gap-2 sm:col-span-2">
                                    <dt className="text-muted-foreground text-sm">
                                        Specifications
                                    </dt>
                                    <dd>
                                        <dl className="divide-border divide-y rounded-xl border">
                                            {product.specifications.map(
                                                (row) => (
                                                    <div
                                                        key={`${row.label}-${row.value}`}
                                                        className="grid gap-1 px-4 py-3 sm:grid-cols-2"
                                                    >
                                                        <dt className="text-muted-foreground text-sm">
                                                            {row.label}
                                                        </dt>
                                                        <dd className="text-sm font-medium">
                                                            {row.value}
                                                        </dd>
                                                    </div>
                                                ),
                                            )}
                                        </dl>
                                    </dd>
                                </div>
                            ) : null}
                        </dl>
                    </CardContent>
                </Card>

                <ProductGallery
                    productName={product.name}
                    images={product.gallery}
                    colorCode={product.color_code}
                />

                <Card>
                    <CardHeader className="flex flex-row flex-wrap items-center justify-between gap-2">
                        <div className="flex flex-col gap-1.5">
                            <CardTitle>Variants</CardTitle>
                            <CardDescription>
                                SKUs and attribute combinations for this product
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
