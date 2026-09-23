import CatalogPricingController from '@/actions/App/Http/Controllers/Branch/CatalogPricingController';
import StockController from '@/actions/App/Http/Controllers/Branch/StockController';
import {
    ManageCatalogPricingPage,
    type CatalogPricingProduct,
} from '@/components/catalog/manage-catalog-pricing-page';
import { usePage } from '@inertiajs/react';

export default function BranchManageCatalogPricing({
    product,
    discountTypes,
}: {
    product: CatalogPricingProduct | null;
    discountTypes: { value: string; label: string }[];
}) {
    const { branch } = usePage().props as {
        branch: { id: number; name: string; slug: string };
    };

    return (
        <ManageCatalogPricingPage
            product={product}
            discountTypes={discountTypes}
            pageUrl={CatalogPricingController.manage.url(branch.slug)}
            productOptionsUrl={StockController.productOptions.url(branch.slug)}
            priceUrl={
                product
                    ? CatalogPricingController.upsert.url({
                          branch: branch.slug,
                          product: product.id,
                      })
                    : null
            }
            breadcrumbs={[
                {
                    title: 'Dashboard',
                    href: `/branch/${branch.slug}/dashboard`,
                },
                {
                    title: 'Products',
                    href: `/branch/${branch.slug}/products`,
                },
                {
                    title: 'Manage pricing',
                    href: CatalogPricingController.manage.url(branch.slug),
                },
            ]}
        />
    );
}
