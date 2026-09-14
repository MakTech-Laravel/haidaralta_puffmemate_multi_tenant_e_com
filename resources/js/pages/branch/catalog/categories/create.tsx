import { Head, setLayoutProps } from '@inertiajs/react';
import CategoryController from '@/actions/App/Http/Controllers/Branch/CategoryController';
import Heading from '@/components/heading';
import {
    CategoryForm,
    type CategoryLimits,
} from '@/components/platform/category-form';

type Branch = { id: number; name: string; slug: string };
type ParentOption = { id: number; name: string };

export default function BranchCategoriesCreate({
    branch,
    parentOptions,
    limits,
}: {
    branch: Branch;
    parentOptions: ParentOption[];
    limits: CategoryLimits;
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

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <Heading
                    title="Add category"
                    description="Create a global catalog category available to every branch"
                />

                <CategoryForm
                    action={CategoryController.store.form(branch.slug)}
                    parentOptions={parentOptions}
                    limits={limits}
                    cancelHref={CategoryController.index.url(branch.slug)}
                    enableSuggestions
                    suggestionsUrl={CategoryController.suggestions.url(
                        branch.slug,
                    )}
                    suggestionShowHref={(slug) =>
                        CategoryController.show.url({
                            branch: branch.slug,
                            category: slug,
                        })
                    }
                />
            </div>
        </>
    );
}
