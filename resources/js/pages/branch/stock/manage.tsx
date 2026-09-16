import { setLayoutProps } from '@inertiajs/react';
import ProductController from '@/actions/App/Http/Controllers/Branch/ProductController';
import StockController from '@/actions/App/Http/Controllers/Branch/StockController';
import { ManageStockPage } from '@/components/stock/manage-stock-page';
import type { StockPageContext } from '@/lib/stock-query';

type Branch = { id: number; name: string; slug: string };

export default function BranchManageStock({
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
                title: 'Manage stock',
                href: StockController.manage.url(branch.slug),
            },
        ],
    });

    return (
        <ManageStockPage
            context={context}
            createProductUrl={createProductUrl}
            pageUrl={StockController.manage.url(branch.slug)}
            adjustUrl={StockController.adjust.url(branch.slug)}
            productOptionsUrl={StockController.productOptions.url(branch.slug)}
        />
    );
}
