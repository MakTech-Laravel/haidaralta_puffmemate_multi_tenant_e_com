import { Head } from '@inertiajs/react';
import ProductController from '@/actions/App/Http/Controllers/Platform/ProductController';
import Heading from '@/components/heading';
import { ProductForm } from '@/components/platform/product-form';

type CategoryOption = {
    id: number;
    name: string;
};

export default function PlatformProductsCreate({
    categoryOptions,
}: {
    categoryOptions: CategoryOption[];
}) {
    return (
        <>
            <Head title="Add product" />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <Heading
                    title="Add product"
                    description="Listing photo, image background, and catalog details shared across every branch"
                />

                <ProductForm
                    action={ProductController.store.form()}
                    categoryOptions={categoryOptions}
                    showBranchNotice
                    enableSuggestions
                    cancelHref={ProductController.index.url()}
                />
            </div>
        </>
    );
}

PlatformProductsCreate.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Products', href: '/platform/products' },
        { title: 'Create', href: '/platform/products/create' },
    ],
};
