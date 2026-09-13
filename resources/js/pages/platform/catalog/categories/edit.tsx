import { Head } from '@inertiajs/react';
import CategoryController from '@/actions/App/Http/Controllers/Platform/CategoryController';
import { CategoryForm } from '@/components/platform/category-form';
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
};

export default function PlatformCategoriesEdit({
    category,
    parentOptions,
}: {
    category: Category;
    parentOptions: ParentOption[];
}) {
    return (
        <>
            <Head title={`Edit ${category.name}`} />

            <div className="flex w-full flex-col gap-6 p-2 md:p-4">
                <Heading
                    title={`Edit ${category.name}`}
                    description="Update category details and media"
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
                    isEdit
                    defaults={{
                        name: category.name,
                        slug: category.slug,
                        parent_id: category.parent_id,
                        image: category.image,
                        image_url: category.image_url,
                        color_code: category.color_code,
                        status: category.status,
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
