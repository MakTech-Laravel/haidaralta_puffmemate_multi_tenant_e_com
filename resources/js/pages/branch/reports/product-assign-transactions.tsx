import { setLayoutProps } from '@inertiajs/react';
import DriverProductAssignController from '@/actions/App/Http/Controllers/Branch/DriverProductAssignController';
import ProductAssignTransactionController from '@/actions/App/Http/Controllers/Branch/Reports/ProductAssignTransactionController';
import type { DataTablePayload } from '@/components/data-table';
import { ProductAssignTransactionsPage } from '@/components/driver/product-assign-transactions-page';
import type { StockPageContext } from '@/lib/stock-query';

type Branch = { id: number; name: string; slug: string };

export default function BranchProductAssignTransactions({
    table,
    context,
}: {
    table: DataTablePayload<never>;
    context: StockPageContext;
}) {
    const branch = context.branch as Branch;

    setLayoutProps({
        breadcrumbs: [
            { title: 'Dashboard', href: `/branch/${branch.slug}/dashboard` },
            {
                title: 'Reports',
                href: ProductAssignTransactionController.index.url(branch.slug),
            },
            {
                title: 'Product assign transactions',
                href: ProductAssignTransactionController.index.url(branch.slug),
            },
        ],
    });

    return (
        <ProductAssignTransactionsPage
            table={table}
            indexUrl={ProductAssignTransactionController.index.url(branch.slug)}
            exportUrl={ProductAssignTransactionController.export.url(
                branch.slug,
            )}
            productOptionsUrl={ProductAssignTransactionController.productOptions.url(
                branch.slug,
            )}
            driverOptionsUrl={DriverProductAssignController.driverOptions.url(
                branch.slug,
            )}
        />
    );
}
