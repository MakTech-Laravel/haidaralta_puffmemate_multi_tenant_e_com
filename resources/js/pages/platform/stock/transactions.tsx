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
            indexUrl={StockController.transactions.url()}
            productOptionsUrl={StockController.productOptions.url()}
            branchOptionsUrl={StockController.branchOptions.url()}
        />
    );
}

PlatformStockTransactions.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Products', href: '/platform/products' },
        { title: 'Stock transactions', href: '/platform/stock/transactions' },
    ],
};
