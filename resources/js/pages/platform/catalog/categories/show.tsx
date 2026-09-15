import { Head } from '@inertiajs/react';
import CategoryController from '@/actions/App/Http/Controllers/Platform/CategoryController';
import {
    CategoryProfile,
    type CategoryProfileRecord,
} from '@/components/platform/category-profile';
import { usePermission } from '@/hooks/use-permissions';

export default function PlatformCategoriesShow({
    category,
}: {
    category: CategoryProfileRecord;
}) {
    const { can } = usePermission();

    return (
        <>
            <Head title={category.name} />

            <CategoryProfile
                category={category}
                indexHref={CategoryController.index.url()}
                editHref={
                    can('manage-catalog')
                        ? CategoryController.edit.url(category.slug)
                        : null
                }
                parentHref={
                    category.parent
                        ? CategoryController.show.url(category.parent.slug)
                        : null
                }
                childHref={(slug) => CategoryController.show.url(slug)}
            />
        </>
    );
}

PlatformCategoriesShow.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Categories', href: '/platform/categories' },
        { title: 'Show', href: '#' },
    ],
};
