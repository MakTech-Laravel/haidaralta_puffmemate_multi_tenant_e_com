import { Head, setLayoutProps } from '@inertiajs/react';
import CategoryController from '@/actions/App/Http/Controllers/Branch/CategoryController';
import Heading from '@/components/heading';
import { CategoryForm } from '@/components/platform/category-form';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import type { ColorPickerValue } from '@/lib/color-picker';

type Branch = { id: number; name: string; slug: string };
type ParentOption = { id: number; name: string };

type Category = {
    id: number;
    name: string;
    slug: string;
    parent_id: number | null;
    image: string | null;
    image_url: string | null;
    color_code?: ColorPickerValue | null;
    status: boolean;
};

export default function BranchCategoriesEdit({
    branch,
    category,
    parentOptions,
}: {
    branch: Branch;
    category: Category;
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
                title: category.name,
                href: CategoryController.show.url({
                    branch: branch.slug,
                    category: category.slug,
                }),
            },
            {
                title: 'Edit',
                href: CategoryController.edit.url({
                    branch: branch.slug,
                    category: category.slug,
                }),
            },
        ],
    });

    return (
        <>
            <Head title={`Edit ${category.name}`} />

            <div className="flex w-full flex-col gap-6 p-2 md:p-4">
                <Heading
                    title={`Edit ${category.name}`}
                    description="Update global category details and media"
                />

                {!category.status ? (
                    <Alert>
                        <AlertTitle>Category inactive</AlertTitle>
                        <AlertDescription>
                            Inactive categories are hidden from storefront
                            browsing.
                        </AlertDescription>
                    </Alert>
                ) : null}

                <CategoryForm
                    action={CategoryController.update.form({
                        branch: branch.slug,
                        category: category.slug,
                    })}
                    parentOptions={parentOptions}
                    isEdit
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
                    excludeCategoryId={category.id}
                    defaults={{
                        name: category.name,
                        slug: category.slug,
                        parent_id: category.parent_id,
                        image: category.image,
                        image_url: category.image_url,
                        color_code: category.color_code,
                        status: category.status,
                    }}
                    cancelHref={CategoryController.show.url({
                        branch: branch.slug,
                        category: category.slug,
                    })}
                />
            </div>
        </>
    );
}
