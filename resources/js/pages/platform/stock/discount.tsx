import StockController from '@/actions/App/Http/Controllers/Platform/StockController';
import { AddDiscountPage } from '@/components/stock/add-discount-page';
import type { StockPageContext } from '@/lib/stock-query';

export default function PlatformAddStockDiscount({
    context,
    createProductUrl,
    discountTypes,
}: {
    context: StockPageContext;
    createProductUrl: string;
    discountTypes: { value: string; label: string }[];
}) {
    return (
        <AddDiscountPage
            context={context}
            createProductUrl={createProductUrl}
            pageUrl={StockController.discount.url()}
            updateUrl={StockController.updateDiscount.url()}
            discountTypes={discountTypes}
            productOptionsUrl={StockController.productOptions.url()}
            branchOptionsUrl={StockController.branchOptions.url()}
        />
    );
}

PlatformAddStockDiscount.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Products', href: '/platform/products' },
        { title: 'Add discount', href: '/platform/stock/discount' },
    ],
};
