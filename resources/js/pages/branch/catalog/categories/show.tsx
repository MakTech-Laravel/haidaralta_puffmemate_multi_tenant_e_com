import { Head, Link, setLayoutProps } from '@inertiajs/react';
import type { ReactNode } from 'react';
import CategoryController from '@/actions/App/Http/Controllers/Branch/CategoryController';
import { COLOR_PICKER_CHECKERBOARD } from '@/components/color-picker/color-swatch-popover';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    colorPickerStyle,
    colorPickerSummary,
    isTransparentColorPickerValue,
    normalizeCatalogColorPickerValue,
    type ColorPickerValue,
} from '@/lib/color-picker';
import { cn } from '@/lib/utils';

type Branch = { id: number; name: string; slug: string };

type Category = {
    id: number;
    name: string;
    slug: string;
    parent_name: string | null;
    image: string | null;
    image_url: string | null;
    color_code?: ColorPickerValue | null;
    status: boolean;
    featured: boolean;
    slogan?: string | null;
    show_in_nav?: boolean;
    nav_sort?: number | null;
    products_count: number;
    children_count: number;
};

function DetailItem({
    label,
    value,
}: {
    label: string;
    value: ReactNode;
}) {
    return (
        <div className="flex flex-col gap-1">
            <dt className="text-muted-foreground text-sm">{label}</dt>
            <dd className="text-sm font-medium">{value}</dd>
        </div>
    );
}

export default function BranchCategoriesShow({
    branch,
    category,
}: {
    branch: Branch;
    category: Category;
}) {
    const colorCode = normalizeCatalogColorPickerValue(category.color_code);
    const transparent = isTransparentColorPickerValue(colorCode);

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

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="flex flex-col gap-2">
                        <Heading
                            title={category.name}
                            description={`/${category.slug}`}
                        />
                        <div className="flex flex-wrap gap-2">
                            <Badge
                                variant={
                                    category.status ? 'default' : 'secondary'
                                }
                                className="w-fit"
                            >
                                {category.status ? 'Active' : 'Inactive'}
                            </Badge>
                            {category.featured ? (
                                <Badge variant="outline" className="w-fit">
                                    Featured
                                </Badge>
                            ) : null}
                            {category.show_in_nav ? (
                                <Badge variant="outline" className="w-fit">
                                    In nav
                                </Badge>
                            ) : null}
                        </div>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button variant="outline" asChild>
                            <Link
                                href={CategoryController.index.url(branch.slug)}
                            >
                                Back
                            </Link>
                        </Button>
                        <Button asChild>
                            <Link
                                href={CategoryController.edit.url({
                                    branch: branch.slug,
                                    category: category.slug,
                                })}
                            >
                                Edit
                            </Link>
                        </Button>
                    </div>
                </div>

                <div className="grid items-stretch gap-6 xl:grid-cols-2">
                    <Card className="h-full">
                        <CardHeader>
                            <CardTitle>Details</CardTitle>
                            <CardDescription>
                                Hierarchy and catalog usage
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <dl className="grid gap-5 sm:grid-cols-2">
                                <DetailItem
                                    label="Parent"
                                    value={category.parent_name ?? '—'}
                                />
                                <DetailItem
                                    label="Products"
                                    value={category.products_count}
                                />
                                <DetailItem
                                    label="Subcategories"
                                    value={category.children_count}
                                />
                                <DetailItem
                                    label="Status"
                                    value={
                                        category.status ? 'Active' : 'Inactive'
                                    }
                                />
                                <DetailItem
                                    label="Featured"
                                    value={category.featured ? 'Yes' : 'No'}
                                />
                                <DetailItem
                                    label="Slogan"
                                    value={category.slogan?.trim() || '—'}
                                />
                                <DetailItem
                                    label="In navigation"
                                    value={
                                        category.show_in_nav ? 'Yes' : 'No'
                                    }
                                />
                                <DetailItem
                                    label="Nav order"
                                    value={
                                        category.show_in_nav
                                            ? (category.nav_sort ?? '—')
                                            : '—'
                                    }
                                />
                                <DetailItem
                                    label="Slug"
                                    value={category.slug}
                                />
                            </dl>
                        </CardContent>
                    </Card>

                    <Card className="h-full overflow-hidden py-0">
                        <CardHeader className="px-6 pt-6">
                            <CardTitle>Listing preview</CardTitle>
                            <CardDescription>
                                Image and background fill
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="px-0 pb-0">
                            <div
                                className={cn(
                                    'flex min-h-52 items-center justify-center px-6 py-8',
                                    transparent && 'bg-muted/25',
                                )}
                                style={
                                    transparent
                                        ? undefined
                                        : colorPickerStyle(colorCode)
                                }
                            >
                                {category.image_url ? (
                                    <img
                                        src={category.image_url}
                                        alt={category.name}
                                        className="max-h-48 max-w-full object-contain"
                                    />
                                ) : (
                                    <p className="rounded-xl border border-dashed border-border/70 bg-background/70 px-4 py-8 text-muted-foreground text-sm backdrop-blur-sm">
                                        No category image
                                    </p>
                                )}
                            </div>
                            <div className="border-border/60 flex items-center justify-between gap-3 border-t px-6 py-3">
                                <div className="min-w-0">
                                    <p className="text-sm font-medium">
                                        Image background
                                    </p>
                                    <p className="text-muted-foreground truncate text-xs">
                                        {colorPickerSummary(colorCode)}
                                    </p>
                                </div>
                                <span
                                    className={cn(
                                        'size-9 shrink-0 rounded-lg border border-border/70 shadow-inner',
                                        transparent && COLOR_PICKER_CHECKERBOARD,
                                    )}
                                    style={
                                        transparent
                                            ? undefined
                                            : colorPickerStyle(colorCode)
                                    }
                                    aria-hidden
                                />
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}
