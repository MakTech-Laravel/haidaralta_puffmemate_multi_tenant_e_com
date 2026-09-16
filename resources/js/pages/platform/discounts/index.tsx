import DiscountController from '@/actions/App/Http/Controllers/Platform/DiscountController';
import StockController from '@/actions/App/Http/Controllers/Platform/StockController';
import { DiscountPage } from '@/components/discounts/discount-page';
import type { StockPageContext } from '@/lib/stock-query';

export default function PlatformDiscounts({
    context,
    createProductUrl,
    discountTypes,
}: {
    context: StockPageContext;
    createProductUrl: string;
    discountTypes: { value: string; label: string }[];
}) {
    return (
        <DiscountPage
            context={context}
            createProductUrl={createProductUrl}
            updateUrl={DiscountController.update.url()}
            previewUrl={DiscountController.preview.url()}
            discountTypes={discountTypes}
            productOptionsUrl={StockController.productOptions.url()}
            branchOptionsUrl={StockController.branchOptions.url()}
        />
    );
}

PlatformDiscounts.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Discounts', href: '/platform/discounts' },
    ],
};
