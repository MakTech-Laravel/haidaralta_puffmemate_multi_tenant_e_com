import ProductAssignTransactionController from '@/actions/App/Http/Controllers/Platform/Reports/ProductAssignTransactionController';
import type { DataTablePayload } from '@/components/data-table';
import { ProductAssignTransactionsPage } from '@/components/driver/product-assign-transactions-page';

export default function PlatformProductAssignTransactions({
    table,
}: {
    table: DataTablePayload<never>;
}) {
    return (
        <ProductAssignTransactionsPage
            table={table}
            indexUrl={ProductAssignTransactionController.index.url()}
            exportUrl={ProductAssignTransactionController.export.url()}
            productOptionsUrl={ProductAssignTransactionController.productOptions.url()}
            driverOptionsUrl={ProductAssignTransactionController.driverOptions.url()}
            branchOptionsUrl={ProductAssignTransactionController.branchOptions.url()}
            hideBranchFilter={false}
            description="History of stock moved onto drivers across branches"
        />
    );
}

PlatformProductAssignTransactions.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        {
            title: 'Reports',
            href: '/platform/reports/product-assign-transactions',
        },
        {
            title: 'Product assign transactions',
            href: '/platform/reports/product-assign-transactions',
        },
    ],
};
