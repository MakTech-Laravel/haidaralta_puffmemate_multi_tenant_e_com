import { setLayoutProps } from '@inertiajs/react';
import ProductController from '@/actions/App/Http/Controllers/Branch/ProductController';
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
            { title: 'Products', href: ProductController.index.url(branch.slug) },
            {
                title: 'Stock transactions',
                href: StockController.transactions.url(branch.slug),
            },
        ],
    });

    return (
        <StockTransactionsPage
            table={table}
            indexUrl={StockController.transactions.url(branch.slug)}
            productOptionsUrl={StockController.productOptions.url(branch.slug)}
            hideBranchFilter
        />
    );
}
