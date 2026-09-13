import { Head } from '@inertiajs/react';
import CategoryController from '@/actions/App/Http/Controllers/Platform/CategoryController';
import { CategoryForm } from '@/components/platform/category-form';
import Heading from '@/components/heading';

type ParentOption = {
    id: number;
    name: string;
};

export default function PlatformCategoriesCreate({
    parentOptions,
}: {
    parentOptions: ParentOption[];
}) {
    return (
        <>
            <Head title="Add category" />

            <div className="mx-auto flex max-w-2xl flex-col gap-6 p-2">
                <Heading
                    title="Add category"
                    description="Create a catalog category"
                />

                <CategoryForm
                    action={CategoryController.store.form()}
                    parentOptions={parentOptions}
                    cancelHref={CategoryController.index.url()}
                />
            </div>
        </>
    );
}

PlatformCategoriesCreate.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Categories', href: '/platform/categories' },
        { title: 'Create', href: '/platform/categories/create' },
    ],
};
