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

            <div className="flex w-full flex-col p-2 md:p-4">
                <Heading
                    title="Add product"
                    description="Create a global catalog product with primary image and gallery"
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
