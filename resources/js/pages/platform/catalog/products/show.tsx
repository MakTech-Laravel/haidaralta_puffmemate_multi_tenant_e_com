import { Head, Link, router, usePage } from '@inertiajs/react';
import { type ColumnDef } from '@tanstack/react-table';
import { format, isValid, parseISO } from 'date-fns';
import { Check, Copy, Network, Pencil, Plus, ScrollText, Settings2, Trash2 } from 'lucide-react';
import { useMemo, useState, type ReactNode } from 'react';
import BranchInventoryController from '@/actions/App/Http/Controllers/Platform/BranchInventoryController';
import CategoryController from '@/actions/App/Http/Controllers/Platform/CategoryController';
import ProductController from '@/actions/App/Http/Controllers/Platform/ProductController';
import ProductVariantController from '@/actions/App/Http/Controllers/Platform/ProductVariantController';
import StockTransactionController from '@/actions/App/Http/Controllers/Platform/Reports/StockTransactionController';
import StockController from '@/actions/App/Http/Controllers/Platform/StockController';
import { ConfirmDeleteDialog } from '@/components/admin/confirm-delete-dialog';
import {
    DataTable,
    DataTableColumnHeader,
    DataTableRowActions,
    DataTableToolbar,
    DataTableTruncated,
    toClientDataTablePayload,
    useDataTableQuery,
    type DataTablePayload,
} from '@/components/data-table';
import { PRODUCT_TAG_OPTIONS } from '@/components/platform/product-form';
import { VariantForm } from '@/components/platform/variant-form';
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
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { usePermission } from '@/hooks/use-permissions';
import {
    colorPickerStyle,
    colorPickerSummary,
    isTransparentColorPickerValue,
    normalizeCatalogColorPickerValue,
    type ColorPickerValue,
} from '@/lib/color-picker';
import { stockActionItems, stockPageUrl } from '@/lib/stock-query';

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

type BranchRow = {
    id: number;
    name: string;
    slug: string;
    code: string | null;
    branch_active: boolean;
    quantity: number;
    selling_price: string | number | null;
    effective_price: string | number | null;
    has_discount: boolean;
    discount_type: string;
    discount_price: string | number | null;
    manage_url: string;
};

function listingPrice(row: BranchRow): ReactNode {
    if (row.selling_price == null && row.effective_price == null) {
        return '—';
    }

    if (row.has_discount && row.selling_price != null) {
        return (
            <span className="tabular-nums">
                <s className="text-muted-foreground">{row.selling_price}</s>{' '}
                {row.effective_price ?? row.selling_price}
            </span>
        );
    }

    return (
        <span className="tabular-nums">
            {row.effective_price ?? row.selling_price}
        </span>
    );
}

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
    table,
}: {
    product: Product;
    table: DataTablePayload<BranchRow>;
}) {
    const { can } = usePermission();
    const { catalog } = usePage().props;
    const [variantDialogOpen, setVariantDialogOpen] = useState(false);
    const [editingVariant, setEditingVariant] = useState<ProductVariant | null>(
        null,
    );
    const [deletingVariantId, setDeletingVariantId] = useState<number | null>(
        null,
    );
    const [deletingProduct, setDeletingProduct] = useState(false);
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

    const branchQuery = useDataTableQuery({
        indexUrl: ProductController.show.url(product.slug),
        initial: {
            filter: {
                search: table.filters.search ?? '',
                missing_price: table.filters.missing_price ?? null,
            },
            sort: table.sort,
            per_page: table.per_page,
        },
    });

    const branchColumns = useMemo<ColumnDef<BranchRow>[]>(
        () => [
            {
                accessorKey: 'name',
                header: ({ column }) => (
                    <DataTableColumnHeader
                        column={column}
                        title="Branch"
                        sortable
                        sortKey="name"
                        currentSort={branchQuery.sort}
                        onSort={branchQuery.toggleSort}
                    />
                ),
                cell: ({ row }) => (
                    <div className="flex min-w-0 flex-col gap-1">
                        <div className="flex min-w-0 flex-wrap items-center gap-2">
                            <DataTableTruncated className="font-medium">
                                {row.original.name}
                            </DataTableTruncated>
                            {!row.original.branch_active ? (
                                <Badge variant="secondary">
                                    Branch inactive
                                </Badge>
                            ) : null}
                        </div>
                        <DataTableTruncated className="text-muted-foreground text-xs">
                            {row.original.code ? `${row.original.code} · ` : ''}
                            /{row.original.slug}
                        </DataTableTruncated>
                    </div>
                ),
                meta: { truncate: false },
            },
            {
                accessorKey: 'quantity',
                header: 'Stock',
                cell: ({ row }) => (
                    <span className="tabular-nums">
                        {row.original.quantity}
                    </span>
                ),
            },
            {
                accessorKey: 'selling_price',
                header: 'Price',
                cell: ({ row }) => listingPrice(row.original),
            },
            {
                id: 'actions',
                header: 'Actions',
                cell: ({ row }) => (
                    <DataTableRowActions
                        contentClassName="w-56"
                        groups={[
                            {
                                label: 'Catalog',
                                items: [
                                    {
                                        label: 'Manage stock',
                                        icon: Settings2,
                                        href: row.original.manage_url,
                                    },
                                ],
                            },
                            {
                                label: 'Stock',
                                items: canManageBranches
                                    ? stockActionItems(
                                          {
                                              transactions:
                                                  StockTransactionController.index.url(),
                                          },
                                          {
                                              productId: product.id,
                                              branchId: row.original.id,
                                          },
                                      )
                                    : [],
                            },
                        ]}
                    />
                ),
            },
        ],
        [
            branchQuery.sort,
            branchQuery.toggleSort,
            canManageBranches,
            product.id,
        ],
    );

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

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
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
                    <div className="flex flex-wrap items-center gap-2">
                        <Button variant="outline" asChild>
                            <Link href={ProductController.index.url()}>
                                All products
                            </Link>
                        </Button>
                        {canManageBranches ? (
                            <Button variant="outline" asChild>
                                <Link
                                    href={stockPageUrl(
                                        StockController.manage.url(),
                                        {
                                            productId: product.id,
                                            locked: 'product',
                                        },
                                    )}
                                >
                                    <Settings2 className="size-4" />
                                    Manage stock
                                </Link>
                            </Button>
                        ) : null}
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
                        {canManageBranches ? (
                            <Button variant="outline" asChild>
                                <Link
                                    href={stockPageUrl(
                                        StockTransactionController.index.url(),
                                        { productId: product.id },
                                    )}
                                >
                                    <ScrollText className="size-4" />
                                    Stock transactions
                                </Link>
                            </Button>
                        ) : null}
                        {canManageCatalog ? (
                            <Button
                                variant="outline"
                                onClick={() => setDeletingProduct(true)}
                            >
                                <Trash2 className="size-4" />
                                Delete
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

                <Card className="gap-4 py-5 shadow-sm">
                    <CardHeader className="px-5">
                        <CardTitle>Listing details</CardTitle>
                        <CardDescription>
                            What customers and operators see for this SKU
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-5 px-5">
                        <div className="grid items-start gap-6 lg:grid-cols-[14rem_minmax(0,1fr)] xl:grid-cols-[16rem_minmax(0,1fr)]">
                            <ProductGallery
                                card={false}
                                layout="tile"
                                productName={product.name}
                                primaryUrl={product.image_url}
                                images={product.gallery}
                                colorCode={product.color_code}
                            />

                            <div className="min-w-0 space-y-5">
                                <p className="text-sm leading-relaxed">
                                    {product.description?.trim() || (
                                        <span className="text-muted-foreground">
                                            No description yet. Add one from
                                            Edit so the storefront has copy for
                                            this product.
                                        </span>
                                    )}
                                </p>

                                <dl className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
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
                                    <Fact
                                        label="Status"
                                        value={
                                            product.status
                                                ? 'Active'
                                                : 'Inactive'
                                        }
                                    />
                                    <Fact
                                        label="Image background"
                                        value={
                                            <span className="inline-flex items-center gap-2">
                                                {transparent ? null : (
                                                    <span
                                                        className="size-5 shrink-0 rounded-md border border-border/70"
                                                        style={colorPickerStyle(
                                                            colorCode,
                                                        )}
                                                        aria-hidden
                                                    />
                                                )}
                                                {colorPickerSummary(colorCode)}
                                            </span>
                                        }
                                    />
                                    <Fact
                                        label="Created"
                                        value={formatTimestamp(
                                            product.created_at,
                                        )}
                                    />
                                    <Fact
                                        label="Updated"
                                        value={formatTimestamp(
                                            product.updated_at,
                                        )}
                                    />
                                    <Fact
                                        label="Created by"
                                        value={product.created_by_name ?? '—'}
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
                                        <p className="text-sm">
                                            {product.category_name}
                                        </p>
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
                            </div>
                        </div>

                        <div className="space-y-3 border-t pt-5">
                            <p className="text-muted-foreground text-[11px] font-medium tracking-wide uppercase">
                                Specifications
                            </p>
                            {product.specifications.length > 0 ? (
                                <div className="grid grid-cols-2 gap-2 sm:grid-cols-3 xl:grid-cols-4">
                                    {product.specifications.map((row) => (
                                        <div
                                            key={`${row.label}-${row.value}`}
                                            className="bg-muted/40 flex min-w-0 flex-col gap-1 rounded-lg px-3 py-2.5"
                                        >
                                            <p className="text-muted-foreground truncate text-[11px] font-medium tracking-wide uppercase">
                                                {row.label}
                                            </p>
                                            <p className="text-sm leading-snug font-semibold wrap-break-word">
                                                {row.value}
                                            </p>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <p className="text-muted-foreground text-sm">
                                    No specifications yet. Add label/value rows
                                    from Edit for nicotine, pack size, or
                                    resistance.
                                </p>
                            )}
                        </div>
                    </CardContent>
                </Card>

                <Card className="gap-4 py-5 shadow-sm">
                    <CardHeader className="flex flex-row flex-wrap items-start justify-between gap-3 px-5">
                        <div className="space-y-1.5">
                            <CardTitle>Branch network</CardTitle>
                            <CardDescription>
                                Set stock and selling price at each branch that
                                should carry this product.
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
                    <CardContent className="px-5">
                        <DataTable
                            columns={branchColumns}
                            payload={table}
                            perPage={branchQuery.perPage}
                            onPerPageChange={branchQuery.changePerPage}
                            emptyMessage="No branches match this filter."
                            toolbar={
                                <DataTableToolbar
                                    search={branchQuery.search}
                                    onSearchChange={branchQuery.setSearch}
                                    searchPlaceholder="Search branch name, slug, or code…"
                                    onClear={branchQuery.clearFilters}
                                    filters={
                                        <div className="grid gap-2">
                                            <Label>Price</Label>
                                            <Select
                                                value={String(
                                                    branchQuery.filters
                                                        .missing_price ?? 'all',
                                                )}
                                                onValueChange={(value) =>
                                                    branchQuery.setFilter(
                                                        'missing_price',
                                                        value === 'all'
                                                            ? null
                                                            : value,
                                                    )
                                                }
                                            >
                                                <SelectTrigger className="w-[10rem]">
                                                    <SelectValue placeholder="Price" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="all">
                                                        All prices
                                                    </SelectItem>
                                                    <SelectItem value="1">
                                                        Missing price
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>
                                    }
                                />
                            }
                        />
                    </CardContent>
                </Card>

                {catalog.show_product_variants ? (
                <Card className="gap-4 py-5 shadow-sm">
                    <CardHeader className="flex flex-row flex-wrap items-center justify-between gap-2 px-5">
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
                    <CardContent className="px-5">
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
                ) : null}
            </div>

            {catalog.show_product_variants ? (
            <Dialog
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
            ) : null}

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

            <ConfirmDeleteDialog
                open={deletingProduct}
                onOpenChange={(open) => {
                    if (!open) {
                        setDeletingProduct(false);
                    }
                }}
                title="Delete product?"
                description="This will soft-delete the product and its variants."
                processing={processing}
                onConfirm={() => {
                    setProcessing(true);
                    router.delete(ProductController.destroy.url(product.slug), {
                        onFinish: () => {
                            setProcessing(false);
                            setDeletingProduct(false);
                        },
                    });
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
