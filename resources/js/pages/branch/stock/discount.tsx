import { setLayoutProps } from '@inertiajs/react';
import ProductController from '@/actions/App/Http/Controllers/Branch/ProductController';
import StockController from '@/actions/App/Http/Controllers/Branch/StockController';
import { AddDiscountPage } from '@/components/stock/add-discount-page';
import type { StockPageContext } from '@/lib/stock-query';

type Branch = { id: number; name: string; slug: string };

export default function BranchAddStockDiscount({
    context,
    createProductUrl,
    discountTypes,
}: {
    context: StockPageContext;
    createProductUrl: string;
    discountTypes: { value: string; label: string }[];
}) {
    const branch = context.branch as Branch;

    setLayoutProps({
        breadcrumbs: [
            { title: 'Dashboard', href: `/branch/${branch.slug}/dashboard` },
            { title: 'Products', href: ProductController.index.url(branch.slug) },
            {
                title: 'Add discount',
                href: StockController.discount.url(branch.slug),
            },
        ],
    });

    return (
        <AddDiscountPage
            context={context}
            createProductUrl={createProductUrl}
            pageUrl={StockController.discount.url(branch.slug)}
            updateUrl={StockController.updateDiscount.url(branch.slug)}
            discountTypes={discountTypes}
        />
    );
}
