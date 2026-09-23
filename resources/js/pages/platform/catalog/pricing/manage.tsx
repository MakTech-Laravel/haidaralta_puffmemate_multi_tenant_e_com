import CatalogPricingController from '@/actions/App/Http/Controllers/Platform/CatalogPricingController';
import StockController from '@/actions/App/Http/Controllers/Platform/StockController';
import {
    ManageCatalogPricingPage,
    type CatalogPricingProduct,
} from '@/components/catalog/manage-catalog-pricing-page';

export default function PlatformManageCatalogPricing({
    product,
    discountTypes,
}: {
    product: CatalogPricingProduct | null;
    discountTypes: { value: string; label: string }[];
}) {
    return (
        <ManageCatalogPricingPage
            product={product}
            discountTypes={discountTypes}
            pageUrl={CatalogPricingController.manage.url()}
            productOptionsUrl={StockController.productOptions.url()}
            priceUrl={
                product
                    ? CatalogPricingController.upsert.url(product.id)
                    : null
            }
            breadcrumbs={[
                { title: 'Dashboard', href: '/platform/dashboard' },
                { title: 'Products', href: '/platform/products' },
                {
                    title: 'Manage pricing',
                    href: CatalogPricingController.manage.url(),
                },
            ]}
        />
    );
}
