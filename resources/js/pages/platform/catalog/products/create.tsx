import { Head } from '@inertiajs/react';
import ProductController from '@/actions/App/Http/Controllers/Platform/ProductController';
import { ProductForm } from '@/components/platform/product-form';
import Heading from '@/components/heading';

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

            <div className="mx-auto flex max-w-2xl flex-col gap-6 p-2">
                <Heading
                    title="Add product"
                    description="Create a catalog product"
                />

                <ProductForm
                    action={ProductController.store.form()}
                    categoryOptions={categoryOptions}
                    showBranchNotice
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
