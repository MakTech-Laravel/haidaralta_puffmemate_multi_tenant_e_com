import StockTransactionController from '@/actions/App/Http/Controllers/Platform/Reports/StockTransactionController';
import StockController from '@/actions/App/Http/Controllers/Platform/StockController';
import type { DataTablePayload } from '@/components/data-table';
import { StockTransactionsPage } from '@/components/stock/stock-transactions-page';

export default function PlatformStockTransactions({
    table,
}: {
    table: DataTablePayload<never>;
}) {
    return (
        <StockTransactionsPage
            table={table}
            indexUrl={StockTransactionController.index.url()}
            exportUrl={StockTransactionController.export.url()}
            productOptionsUrl={StockController.productOptions.url()}
            branchOptionsUrl={StockController.branchOptions.url()}
        />
    );
}

PlatformStockTransactions.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Reports', href: '/platform/reports/stock-transactions' },
        {
            title: 'Stock transactions',
            href: '/platform/reports/stock-transactions',
        },
    ],
};
