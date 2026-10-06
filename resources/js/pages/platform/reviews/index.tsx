import { Head, router } from '@inertiajs/react';
import { type ColumnDef } from '@tanstack/react-table';
import { Eye, EyeOff } from 'lucide-react';
import { useMemo, useState } from 'react';
import ProductReviewController from '@/actions/App/Http/Controllers/Platform/ProductReviewController';
import {
    DataTable,
    DataTableColumnHeader,
    DataTableRowActions,
    DataTableToolbar,
    DataTableTruncated,
    useDataTableQuery,
    type DataTablePayload,
} from '@/components/data-table';
import Heading from '@/components/heading';
import { InfiniteScrollCombobox } from '@/components/stock/infinite-scroll-combobox';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatAppDateTime } from '@/lib/datetime';

type ReviewRow = {
    id: number;
    customer_name: string | null;
    product_name: string | null;
    order_number: string | null;
    branch_name: string | null;
    body: string;
    rating: number;
    hidden: boolean;
    created_at: string | null;
};

type SelectedCustomer = {
    id: number;
    name: string;
    phone?: string | null;
    email?: string | null;
};

type SelectedProduct = {
    id: number;
    name: string;
    sku?: string | null;
};

type SelectedBranch = {
    id: number;
    name: string;
    slug?: string | null;
};

export default function PlatformReviewsIndex({
    table: tablePayload,
    customerOptionsUrl,
    productOptionsUrl,
    branchOptionsUrl,
}: {
    table: DataTablePayload<ReviewRow> & {
        filterOptions?: {
            selected_customer?: SelectedCustomer | null;
            selected_product?: SelectedProduct | null;
            selected_branch?: SelectedBranch | null;
        };
    };
    customerOptionsUrl: string;
    productOptionsUrl: string;
    branchOptionsUrl: string;
}) {
    const query = useDataTableQuery({
        indexUrl: ProductReviewController.index.url(),
        initial: {
            filter: {
                search: tablePayload.filters.search ?? '',
                user_id: tablePayload.filters.user_id ?? 'all',
                product_id: tablePayload.filters.product_id ?? 'all',
                branch_id: tablePayload.filters.branch_id ?? 'all',
                order_number: tablePayload.filters.order_number ?? '',
            },
            sort: tablePayload.sort,
            per_page: tablePayload.per_page,
        },
    });

    const [orderNumberDraft, setOrderNumberDraft] = useState(
        String(tablePayload.filters.order_number ?? ''),
    );

    const selectedCustomer = tablePayload.filterOptions?.selected_customer;
    const selectedProduct = tablePayload.filterOptions?.selected_product;
    const selectedBranch = tablePayload.filterOptions?.selected_branch;
    const customerFilter = String(query.filters.user_id ?? 'all');
    const productFilter = String(query.filters.product_id ?? 'all');
    const branchFilter = String(query.filters.branch_id ?? 'all');

    const selectedCustomerLabel =
        selectedCustomer !== undefined && selectedCustomer !== null
            ? selectedCustomer.phone
                ? `${selectedCustomer.name} · ${selectedCustomer.phone}`
                : selectedCustomer.name
            : null;

    const selectedProductLabel =
        selectedProduct !== undefined && selectedProduct !== null
            ? selectedProduct.sku
                ? `${selectedProduct.name} · ${selectedProduct.sku}`
                : selectedProduct.name
            : null;

    const applyOrderNumber = () => {
        const next = orderNumberDraft.trim();
        query.setFilter('order_number', next === '' ? null : next);
    };

    const columns = useMemo<ColumnDef<ReviewRow>[]>(
        () => [
            {
                accessorKey: 'customer_name',
                header: 'Customer',
                cell: ({ row }) => (
                    <DataTableTruncated className="font-medium">
                        {row.original.customer_name ?? '—'}
                    </DataTableTruncated>
                ),
            },
            {
                accessorKey: 'product_name',
                header: 'Product',
                cell: ({ row }) => (
                    <DataTableTruncated>
                        {row.original.product_name ?? '—'}
                    </DataTableTruncated>
                ),
            },
            {
                accessorKey: 'order_number',
                header: 'Order ID',
                cell: ({ row }) => row.original.order_number ?? '—',
            },
            {
                accessorKey: 'branch_name',
                header: 'Branch',
                cell: ({ row }) => (
                    <DataTableTruncated>
                        {row.original.branch_name ?? '—'}
                    </DataTableTruncated>
                ),
            },
            {
                accessorKey: 'body',
                header: 'Review',
                cell: ({ row }) => (
                    <DataTableTruncated>{row.original.body}</DataTableTruncated>
                ),
            },
            {
                id: 'visibility',
                header: 'Visibility',
                cell: ({ row }) =>
                    row.original.hidden ? (
                        <Badge variant="outline">Hidden</Badge>
                    ) : (
                        <Badge variant="secondary">Visible</Badge>
                    ),
            },
            {
                accessorKey: 'created_at',
                header: ({ column }) => (
                    <DataTableColumnHeader
                        column={column}
                        title="Created at"
                        sortable
                        sortKey="created_at"
                        currentSort={query.sort}
                        onSort={query.toggleSort}
                    />
                ),
                cell: ({ row }) =>
                    formatAppDateTime(row.original.created_at) || '—',
            },
            {
                id: 'actions',
                header: 'Actions',
                cell: ({ row }) => {
                    const review = row.original;

                    return (
                        <DataTableRowActions
                            items={[
                                review.hidden
                                    ? {
                                          label: 'Unhide',
                                          icon: Eye,
                                          onSelect: () => {
                                              router.post(
                                                  ProductReviewController.unhide.url(
                                                      review.id,
                                                  ),
                                              );
                                          },
                                      }
                                    : {
                                          label: 'Hide',
                                          icon: EyeOff,
                                          onSelect: () => {
                                              router.post(
                                                  ProductReviewController.hide.url(
                                                      review.id,
                                                  ),
                                              );
                                          },
                                      },
                                {
                                    label: 'Details',
                                    icon: Eye,
                                    disabled: true,
                                },
                            ]}
                        />
                    );
                },
            },
        ],
        [query.sort, query.toggleSort],
    );

    return (
        <>
            <Head title="Reviews" />

            <div className="space-y-4 p-2 md:p-4">
                <Heading
                    title="Reviews"
                    description="Moderate customer product reviews across the platform."
                />

                <DataTable
                    columns={columns}
                    payload={tablePayload}
                    perPage={query.perPage}
                    isLoading={query.processing}
                    onPerPageChange={query.changePerPage}
                    emptyMessage="No reviews yet."
                    toolbar={
                        <DataTableToolbar
                            search={query.search}
                            onSearchChange={query.setSearch}
                            searchPlaceholder="Search customer, product, order, branch, or review…"
                            onClear={() => {
                                setOrderNumberDraft('');
                                query.clearFilters();
                            }}
                            filters={
                                <>
                                    <div className="grid gap-2">
                                        <Label>Customer</Label>
                                        <InfiniteScrollCombobox
                                            optionsUrl={customerOptionsUrl}
                                            value={
                                                customerFilter === 'all'
                                                    ? null
                                                    : customerFilter
                                            }
                                            onValueChange={(value) =>
                                                query.setFilter(
                                                    'user_id',
                                                    value,
                                                )
                                            }
                                            selectedLabel={selectedCustomerLabel}
                                            placeholder="All customers"
                                            searchPlaceholder="Search customers…"
                                            emptyText="No customer found."
                                            noneLabel="All customers"
                                            descriptionKey="sku"
                                            className="w-56"
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label>Product</Label>
                                        <InfiniteScrollCombobox
                                            optionsUrl={productOptionsUrl}
                                            value={
                                                productFilter === 'all'
                                                    ? null
                                                    : productFilter
                                            }
                                            onValueChange={(value) =>
                                                query.setFilter(
                                                    'product_id',
                                                    value,
                                                )
                                            }
                                            selectedLabel={selectedProductLabel}
                                            placeholder="All products"
                                            searchPlaceholder="Search products…"
                                            emptyText="No product found."
                                            noneLabel="All products"
                                            descriptionKey="sku"
                                            className="w-56"
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label>Order ID</Label>
                                        <Input
                                            value={orderNumberDraft}
                                            onChange={(event) =>
                                                setOrderNumberDraft(
                                                    event.target.value,
                                                )
                                            }
                                            onBlur={applyOrderNumber}
                                            onKeyDown={(event) => {
                                                if (event.key === 'Enter') {
                                                    applyOrderNumber();
                                                }
                                            }}
                                            placeholder="Order number"
                                            className="w-44"
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label>Branch</Label>
                                        <InfiniteScrollCombobox
                                            optionsUrl={branchOptionsUrl}
                                            value={
                                                branchFilter === 'all'
                                                    ? null
                                                    : branchFilter
                                            }
                                            onValueChange={(value) =>
                                                query.setFilter(
                                                    'branch_id',
                                                    value,
                                                )
                                            }
                                            selectedLabel={
                                                selectedBranch?.name ?? null
                                            }
                                            placeholder="All branches"
                                            searchPlaceholder="Search branches…"
                                            emptyText="No branch found."
                                            noneLabel="All branches"
                                            descriptionKey={null}
                                            className="w-52"
                                        />
                                    </div>
                                </>
                            }
                        />
                    }
                />
            </div>
        </>
    );
}

PlatformReviewsIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        {
            title: 'Reviews',
            href: '/platform/reviews',
        },
    ],
};
