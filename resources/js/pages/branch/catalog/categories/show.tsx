import { Head, setLayoutProps } from '@inertiajs/react';
import CategoryController from '@/actions/App/Http/Controllers/Branch/CategoryController';
import {
    CategoryProfile,
    type CategoryProfileRecord,
} from '@/components/platform/category-profile';

type Branch = { id: number; name: string; slug: string };

export default function BranchCategoriesShow({
    branch,
    category,
}: {
    branch: Branch;
    category: CategoryProfileRecord;
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
        ],
    });

    return (
        <>
            <Head title={category.name} />

            <CategoryProfile
                category={category}
                indexHref={CategoryController.index.url(branch.slug)}
                editHref={CategoryController.edit.url({
                    branch: branch.slug,
                    category: category.slug,
                })}
                parentHref={
                    category.parent
                        ? CategoryController.show.url({
                              branch: branch.slug,
                              category: category.parent.slug,
                          })
                        : null
                }
                childHref={(slug) =>
                    CategoryController.show.url({
                        branch: branch.slug,
                        category: slug,
                    })
                }
            />
        </>
    );
}
