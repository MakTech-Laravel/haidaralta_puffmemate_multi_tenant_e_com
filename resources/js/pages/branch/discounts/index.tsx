import { setLayoutProps } from '@inertiajs/react';
import DiscountController from '@/actions/App/Http/Controllers/Branch/DiscountController';
import StockController from '@/actions/App/Http/Controllers/Branch/StockController';
import { DiscountPage } from '@/components/discounts/discount-page';
import type { StockPageContext } from '@/lib/stock-query';

type Branch = { id: number; name: string; slug: string };

export default function BranchDiscounts({
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
            {
                title: 'Discounts',
                href: DiscountController.index.url(branch.slug),
            },
        ],
    });

    return (
        <DiscountPage
            context={context}
            createProductUrl={createProductUrl}
            updateUrl={DiscountController.update.url(branch.slug)}
            previewUrl={DiscountController.preview.url(branch.slug)}
            discountTypes={discountTypes}
            productOptionsUrl={StockController.productOptions.url(branch.slug)}
            lockBranch
        />
    );
}
