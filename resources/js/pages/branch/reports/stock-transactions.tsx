import { setLayoutProps } from '@inertiajs/react';
import StockTransactionController from '@/actions/App/Http/Controllers/Branch/Reports/StockTransactionController';
import StockController from '@/actions/App/Http/Controllers/Branch/StockController';
import type { DataTablePayload } from '@/components/data-table';
import { StockTransactionsPage } from '@/components/stock/stock-transactions-page';
import type { StockPageContext } from '@/lib/stock-query';

type Branch = { id: number; name: string; slug: string };

export default function BranchStockTransactions({
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
                href: StockTransactionController.index.url(branch.slug),
            },
            {
                title: 'Stock transactions',
                href: StockTransactionController.index.url(branch.slug),
            },
        ],
    });

    return (
        <StockTransactionsPage
            table={table}
            indexUrl={StockTransactionController.index.url(branch.slug)}
            productOptionsUrl={StockController.productOptions.url(branch.slug)}
            hideBranchFilter
        />
    );
}
