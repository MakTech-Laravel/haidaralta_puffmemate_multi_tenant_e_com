import { Head, setLayoutProps } from '@inertiajs/react';
import CategoryController from '@/actions/App/Http/Controllers/Branch/CategoryController';
import Heading from '@/components/heading';
import { CategoryForm } from '@/components/platform/category-form';

type Branch = { id: number; name: string; slug: string };
type ParentOption = { id: number; name: string };

export default function BranchCategoriesCreate({
    branch,
    parentOptions,
}: {
    branch: Branch;
    parentOptions: ParentOption[];
}) {
    setLayoutProps({
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: `/branch/${branch.slug}/dashboard`,
            },
            {
                title: 'Categories',
                href: CategoryController.index.url(branch.slug),
            },
            {
                title: 'Create',
                href: CategoryController.create.url(branch.slug),
            },
        ],
    });

    return (
        <>
            <Head title="Add category" />

            <div className="flex w-full flex-col gap-6 p-2 md:p-4">
                <Heading
                    title="Add category"
                    description="Create a global catalog category available to every branch"
                />

                <CategoryForm
                    action={CategoryController.store.form(branch.slug)}
                    parentOptions={parentOptions}
                    cancelHref={CategoryController.index.url(branch.slug)}
                />
            </div>
        </>
    );
}
