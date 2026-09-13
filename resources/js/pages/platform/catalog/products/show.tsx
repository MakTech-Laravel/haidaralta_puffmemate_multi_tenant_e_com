import { Head, Link, router } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import ProductController from '@/actions/App/Http/Controllers/Platform/ProductController';
import ProductVariantController from '@/actions/App/Http/Controllers/Platform/ProductVariantController';
import { ConfirmDeleteDialog } from '@/components/admin/confirm-delete-dialog';
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
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { usePermission } from '@/hooks/use-permissions';

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

    return (
        <>
            <Head title={product.name} />

            <div className="flex flex-col gap-6 p-2">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="flex items-start gap-4">
                        <Avatar className="size-14 rounded-xl">
                            {product.image_url ? (
                                <AvatarImage
                                    src={product.image_url}
                                    alt={product.name}
                                />
                            ) : null}
                            <AvatarFallback className="rounded-xl text-base">
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
                                label="Category"
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
                    <CardHeader className="flex flex-row flex-wrap items-center justify-between gap-2">
                        <div className="flex flex-col gap-1.5">
                            <CardTitle>Variants</CardTitle>
                            <CardDescription>
                                SKUs and attribute combinations for this product
                            </CardDescription>
                        </div>
                        {can('manage-catalog') ? (
                            <Button size="sm" onClick={openCreateVariant}>
                                <Plus className="size-4" />
                                Add variant
                            </Button>
                        ) : null}
                    </CardHeader>
                    <CardContent>
                        {product.variants.length === 0 ? (
                            <p className="text-muted-foreground text-sm">
                                No variants yet. Add one to define purchasable
                                SKUs.
                            </p>
                        ) : (
                            <div className="overflow-hidden rounded-lg border">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Name</TableHead>
                                            <TableHead>SKU</TableHead>
                                            <TableHead>Attributes</TableHead>
                                            <TableHead>Status</TableHead>
                                            {can('manage-catalog') ? (
                                                <TableHead className="text-right">
                                                    Actions
                                                </TableHead>
                                            ) : null}
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {product.variants.map((variant) => (
                                            <TableRow key={variant.id}>
                                                <TableCell className="font-medium">
                                                    {variant.name}
                                                </TableCell>
                                                <TableCell>
                                                    {variant.sku ?? '—'}
                                                </TableCell>
                                                <TableCell className="max-w-xs truncate font-mono text-xs">
                                                    {variant.attributes
                                                        ? JSON.stringify(
                                                              variant.attributes,
                                                          )
                                                        : '—'}
                                                </TableCell>
                                                <TableCell>
                                                    <Badge
                                                        variant={
                                                            variant.status
                                                                ? 'default'
                                                                : 'secondary'
                                                        }
                                                    >
                                                        {variant.status
                                                            ? 'Active'
                                                            : 'Inactive'}
                                                    </Badge>
                                                </TableCell>
                                                {can('manage-catalog') ? (
                                                    <TableCell className="text-right">
                                                        <div className="flex justify-end gap-1">
                                                            <Button
                                                                type="button"
                                                                variant="ghost"
                                                                size="sm"
                                                                aria-label={`Edit ${variant.name}`}
                                                                onClick={() =>
                                                                    openEditVariant(
                                                                        variant,
                                                                    )
                                                                }
                                                            >
                                                                <Pencil className="size-4" />
                                                                <span className="sr-only">
                                                                    Edit
                                                                </span>
                                                            </Button>
                                                            <Button
                                                                type="button"
                                                                variant="ghost"
                                                                size="sm"
                                                                aria-label={`Delete ${variant.name}`}
                                                                onClick={() =>
                                                                    setDeletingVariantId(
                                                                        variant.id,
                                                                    )
                                                                }
                                                            >
                                                                <Trash2 className="text-destructive size-4" />
                                                                <span className="sr-only">
                                                                    Delete
                                                                </span>
                                                            </Button>
                                                        </div>
                                                    </TableCell>
                                                ) : null}
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        )}
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
