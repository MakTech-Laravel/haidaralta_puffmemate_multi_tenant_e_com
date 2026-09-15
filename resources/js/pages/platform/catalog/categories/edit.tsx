import { Head } from '@inertiajs/react';
import CategoryController from '@/actions/App/Http/Controllers/Platform/CategoryController';
import {
    CategoryForm,
    type CategoryLimits,
} from '@/components/platform/category-form';
import Heading from '@/components/heading';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import type { ColorPickerValue } from '@/lib/color-picker';

type ParentOption = {
    id: number;
    name: string;
};

type Category = {
    id: number;
    name: string;
    slug: string;
    parent_id: number | null;
    image: string | null;
    image_url: string | null;
    color_code?: ColorPickerValue | null;
    status: boolean;
    featured: boolean;
    slogan?: string | null;
    show_in_nav?: boolean;
    nav_sort?: number | null;
};

export default function PlatformCategoriesEdit({
    category,
    parentOptions,
    limits,
}: {
    category: Category;
    parentOptions: ParentOption[];
    limits: CategoryLimits;
}) {
    return (
        <>
            <Head title={`Edit ${category.name}`} />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <Heading
                    title={`Edit ${category.name}`}
                    description="Update listing photo, image background, and catalog details"
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
                    action={CategoryController.update.form(category.slug)}
                    parentOptions={parentOptions}
                    limits={limits}
                    isEdit
                    enableSuggestions
                    suggestionsUrl={CategoryController.suggestions.url()}
                    suggestionShowHref={(slug) =>
                        CategoryController.show.url(slug)
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
                        featured: category.featured,
                        slogan: category.slogan,
                        show_in_nav: category.show_in_nav,
                        nav_sort: category.nav_sort,
                    }}
                    cancelHref={CategoryController.show.url(category.slug)}
                />
            </div>
        </>
    );
}

PlatformCategoriesEdit.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Categories', href: '/platform/categories' },
        { title: 'Edit', href: '#' },
    ],
};
