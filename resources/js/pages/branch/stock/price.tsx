import { setLayoutProps } from '@inertiajs/react';
import ProductController from '@/actions/App/Http/Controllers/Branch/ProductController';
import StockController from '@/actions/App/Http/Controllers/Branch/StockController';
import { UpdatePricePage } from '@/components/stock/update-price-page';
import type { StockPageContext } from '@/lib/stock-query';

type Branch = { id: number; name: string; slug: string };

export default function BranchUpdateStockPrice({
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
                title: 'Update price',
                href: StockController.price.url(branch.slug),
            },
        ],
    });

    return (
        <UpdatePricePage
            context={context}
            createProductUrl={createProductUrl}
            pageUrl={StockController.price.url(branch.slug)}
            updateUrl={StockController.updatePrice.url(branch.slug)}
            productOptionsUrl={StockController.productOptions.url(branch.slug)}
        />
    );
}
