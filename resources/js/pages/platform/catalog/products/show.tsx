import { Head, Link, router } from '@inertiajs/react';
import { type ColumnDef } from '@tanstack/react-table';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';
import type { ReactNode } from 'react';
import ProductController from '@/actions/App/Http/Controllers/Platform/ProductController';
import ProductVariantController from '@/actions/App/Http/Controllers/Platform/ProductVariantController';
import { ConfirmDeleteDialog } from '@/components/admin/confirm-delete-dialog';
import {
    DataTable,
    DataTableRowActions,
    toClientDataTablePayload,
} from '@/components/data-table';
import Heading from '@/components/heading';
import { VariantForm } from '@/components/platform/variant-form';
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
    type ColorPickerValue,
} from '@/lib/color-picker';

type ProductVariant = {
    id: number;
    name: string;
    sku: string | null;
    attributes: Record<string, string> | null;
    status: boolean;
};

type Product = {
    id: number;
    name: string;
    slug: string;
    sku: string | null;
    description: string | null;
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
                            className="size-14 rounded-xl"
                            style={
                                isTransparentColorPickerValue(product.color_code)
                                    ? undefined
                                    : colorPickerStyle(product.color_code)
                            }
                        >
                            {product.image_url ? (
                                <AvatarImage
                                    src={product.image_url}
                                    alt={product.name}
                                />
                            ) : null}
                            <AvatarFallback className="rounded-xl bg-transparent text-base">
                                {product.name.slice(0, 2).toUpperCase()}
                            </AvatarFallback>
                        </Avatar>
                        <div className="flex flex-col gap-2">
                            <Heading
                                title={product.name}
                                description={`/${product.slug}${product.sku ? ` · ${product.sku}` : ''}`}
                            />
                            <Badge
                                variant={
                                    product.status ? 'default' : 'secondary'
                                }
                            >
                                {product.status ? 'Active' : 'Inactive'}
                            </Badge>
                        </div>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button variant="default" asChild>
                            <Link href={ProductController.index.url()}>
                                Back
                            </Link>
                        </Button>
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
                    <AlertDescription>
                        Disabled for all branches — enable from branch inventory.
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
                                label="Categories"
                                value={product.category_name}
                            />
                            <DetailItem
                                label="SKU"
                                value={product.sku ?? '—'}
                            />
                            <DetailItem
                                label="Slug"
                                value={product.slug}
                            />
                            <DetailItem
                                label="Description"
                                value={product.description ?? '—'}
                            />
                        </dl>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Gallery</CardTitle>
                        <CardDescription>
                            Extra images shown on the product page
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {product.gallery.length === 0 ? (
                            <p className="text-muted-foreground text-sm">
                                No gallery images yet.
                            </p>
                        ) : (
                            <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                                {product.gallery.map((image) => (
                                    <div
                                        key={image.id}
                                        className="overflow-hidden rounded-xl border bg-muted/20"
                                    >
                                        <img
                                            src={image.url}
                                            alt={`${product.name} gallery`}
                                            className="aspect-square w-full object-cover"
                                        />
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>

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
                                ? ProductVariantController.update.form(
                                      product.slug,
                                      editingVariant.id,
                                  )
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
                        ProductVariantController.destroy.url(
                            product.slug,
                            deletingVariantId,
                        ),
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
