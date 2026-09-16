import { setLayoutProps } from '@inertiajs/react';
import ProductController from '@/actions/App/Http/Controllers/Branch/ProductController';
import StockController from '@/actions/App/Http/Controllers/Branch/StockController';
import { ImportStockPage } from '@/components/stock/import-stock-page';
import type { StockPageContext } from '@/lib/stock-query';

type Branch = { id: number; name: string; slug: string };

export default function BranchImportStock({
    context,
    createProductUrl,
}: {
    context: StockPageContext;
    createProductUrl: string;
}) {
    const branch = context.branch as Branch;

    setLayoutProps({
        breadcrumbs: [
            { title: 'Dashboard', href: `/branch/${branch.slug}/dashboard` },
            { title: 'Products', href: ProductController.index.url(branch.slug) },
            {
                title: 'Import stock',
                href: StockController.import.url(branch.slug),
            },
        ],
    });

    return (
        <ImportStockPage
            context={context}
            createProductUrl={createProductUrl}
            storeUrl={StockController.storeImport.url(branch.slug)}
            productOptionsUrl={StockController.productOptions.url(branch.slug)}
            lockBranch
        />
    );
}
