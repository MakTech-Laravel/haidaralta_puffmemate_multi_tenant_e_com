import { router, usePage } from '@inertiajs/react';
import { type ColumnDef } from '@tanstack/react-table';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';
import ProductPackageController from '@/actions/App/Http/Controllers/Platform/ProductPackageController';
import { ConfirmDeleteDialog } from '@/components/admin/confirm-delete-dialog';
import {
    DataTable,
    DataTableRowActions,
    DataTableTruncated,
    toClientDataTablePayload,
} from '@/components/data-table';
import { ProductPackageForm } from '@/components/platform/product-package-form';
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
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { usePermission } from '@/hooks/use-permissions';

export type CatalogProductPackage = {
    id: number;
    label: string;
    quantity: number;
    price: string | number;
    status: boolean;
    sort_order: number;
};

type ProductPackagesCardProps = {
    productSlug: string;
    productName: string;
    packagesEnabled: boolean;
    packages: CatalogProductPackage[];
};

export function ProductPackagesCard({
    productSlug,
    productName,
    packagesEnabled,
    packages,
}: ProductPackagesCardProps) {
    const { catalog } = usePage().props;
    const { can } = usePermission();
    const canManageCatalog = can('manage-catalog');
    const [dialogOpen, setDialogOpen] = useState(false);
    const [editingPackage, setEditingPackage] =
        useState<CatalogProductPackage | null>(null);
    const [deletingPackageId, setDeletingPackageId] = useState<number | null>(
        null,
    );
    const [processing, setProcessing] = useState(false);
    const [togglingEnabled, setTogglingEnabled] = useState(false);

    const openCreate = () => {
        setEditingPackage(null);
        setDialogOpen(true);
    };

    const openEdit = (pkg: CatalogProductPackage) => {
        setEditingPackage(pkg);
        setDialogOpen(true);
    };

    const closeDialog = () => {
        setDialogOpen(false);
        setEditingPackage(null);
    };

    const columns = useMemo<ColumnDef<CatalogProductPackage>[]>(() => {
        const cols: ColumnDef<CatalogProductPackage>[] = [
            {
                accessorKey: 'label',
                header: 'Label',
                cell: ({ row }) => (
                    <DataTableTruncated className="font-medium">
                        {row.original.label}
                    </DataTableTruncated>
                ),
            },
            {
                accessorKey: 'quantity',
                header: 'Quantity',
                cell: ({ row }) => (
                    <span className="tabular-nums">{row.original.quantity}</span>
                ),
            },
            {
                accessorKey: 'price',
                header: 'Price',
                cell: ({ row }) => (
                    <span className="tabular-nums">{row.original.price}</span>
                ),
            },
            {
                accessorKey: 'status',
                header: 'Status',
                cell: ({ row }) => (
                    <Badge
                        variant={row.original.status ? 'default' : 'secondary'}
                    >
                        {row.original.status ? 'Enabled' : 'Disabled'}
                    </Badge>
                ),
            },
        ];

        if (canManageCatalog) {
            cols.push({
                id: 'actions',
                header: 'Actions',
                cell: ({ row }) => (
                    <div className="flex justify-end">
                        <DataTableRowActions
                            items={[
                                {
                                    label: 'Edit',
                                    icon: Pencil,
                                    onSelect: () => openEdit(row.original),
                                },
                                {
                                    label: 'Delete',
                                    icon: Trash2,
                                    destructive: true,
                                    separatorBefore: true,
                                    onSelect: () =>
                                        setDeletingPackageId(row.original.id),
                                },
                            ]}
                        />
                    </div>
                ),
            });
        }

        return cols;
    }, [canManageCatalog]);

    const payload = useMemo(
        () => toClientDataTablePayload(packages ?? []),
        [packages],
    );

    if (!catalog.show_product_packages) {
        return null;
    }

    return (
        <>
            <Card className="gap-4 py-5 shadow-sm">
                <CardHeader className="flex flex-row flex-wrap items-center justify-between gap-2 px-5">
                    <div className="flex flex-col gap-1.5">
                        <CardTitle>Packages</CardTitle>
                        <CardDescription>
                            Quantity tiers and package prices for this product
                        </CardDescription>
                    </div>
                    <div className="flex flex-wrap items-center gap-3">
                        {canManageCatalog ? (
                            <div className="flex items-center gap-2">
                                <Switch
                                    id="packages-enabled"
                                    checked={packagesEnabled}
                                    disabled={togglingEnabled}
                                    onCheckedChange={(checked) => {
                                        setTogglingEnabled(true);
                                        router.put(
                                            ProductPackageController.updateEnabled.url(
                                                productSlug,
                                            ),
                                            {
                                                packages_enabled: checked,
                                            },
                                            {
                                                preserveScroll: true,
                                                onFinish: () =>
                                                    setTogglingEnabled(false),
                                            },
                                        );
                                    }}
                                />
                                <Label
                                    htmlFor="packages-enabled"
                                    className="text-sm font-normal"
                                >
                                    Available on storefront
                                </Label>
                            </div>
                        ) : (
                            <Badge
                                variant={
                                    packagesEnabled ? 'default' : 'secondary'
                                }
                            >
                                {packagesEnabled
                                    ? 'Storefront on'
                                    : 'Storefront off'}
                            </Badge>
                        )}
                        {canManageCatalog ? (
                            <Button size="sm" onClick={openCreate}>
                                <Plus className="size-4" />
                                Add package
                            </Button>
                        ) : null}
                    </div>
                </CardHeader>
                <CardContent className="px-5">
                    <DataTable
                        columns={columns}
                        payload={payload}
                        perPage={payload.per_page}
                        onPerPageChange={() => undefined}
                        showPagination={false}
                        emptyMessage="No packages yet. Add one to offer quantity tiers."
                    />
                </CardContent>
            </Card>

            <Dialog
                open={dialogOpen}
                onOpenChange={(open) => {
                    setDialogOpen(open);

                    if (!open) {
                        setEditingPackage(null);
                    }
                }}
            >
                <DialogContent className="sm:max-w-lg">
                    <DialogHeader>
                        <DialogTitle>
                            {editingPackage ? 'Edit package' : 'Add package'}
                        </DialogTitle>
                        <DialogDescription>
                            Packages define quantity tiers and prices for{' '}
                            {productName}.
                        </DialogDescription>
                    </DialogHeader>
                    <ProductPackageForm
                        action={
                            editingPackage
                                ? ProductPackageController.update.form({
                                      product: productSlug,
                                      package: editingPackage.id,
                                  })
                                : ProductPackageController.store.form(
                                      productSlug,
                                  )
                        }
                        isEdit={editingPackage !== null}
                        defaults={
                            editingPackage
                                ? {
                                      label: editingPackage.label,
                                      quantity: editingPackage.quantity,
                                      price: editingPackage.price,
                                      status: editingPackage.status,
                                      sort_order: editingPackage.sort_order,
                                  }
                                : undefined
                        }
                        onCancel={closeDialog}
                        onSuccess={closeDialog}
                    />
                </DialogContent>
            </Dialog>

            <ConfirmDeleteDialog
                open={deletingPackageId !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setDeletingPackageId(null);
                    }
                }}
                description="This will soft-delete the package."
                processing={processing}
                onConfirm={() => {
                    if (deletingPackageId === null) {
                        return;
                    }

                    setProcessing(true);
                    router.delete(
                        ProductPackageController.destroy.url({
                            product: productSlug,
                            package: deletingPackageId,
                        }),
                        {
                            preserveScroll: true,
                            onFinish: () => {
                                setProcessing(false);
                                setDeletingPackageId(null);
                            },
                        },
                    );
                }}
            />
        </>
    );
}
