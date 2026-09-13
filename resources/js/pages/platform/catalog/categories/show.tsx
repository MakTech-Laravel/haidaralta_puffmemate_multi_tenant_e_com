import { Head, Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import CategoryController from '@/actions/App/Http/Controllers/Platform/CategoryController';
import Heading from '@/components/heading';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { usePermission } from '@/hooks/use-permissions';
import {
    colorPickerStyle,
    isTransparentColorPickerValue,
    type ColorPickerValue,
} from '@/lib/color-picker';

type Category = {
    id: number;
    name: string;
    slug: string;
    parent_name: string | null;
    image: string | null;
    image_url: string | null;
    color_code?: ColorPickerValue | null;
    status: boolean;
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

export default function PlatformCategoriesShow({
    category,
}: {
    category: Category;
}) {
    const { can } = usePermission();

    return (
        <>
            <Head title={category.name} />

            <div className="flex flex-col gap-6 p-2">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="flex items-start gap-4">
                        <Avatar
                            className="size-14 rounded-xl"
                            style={
                                isTransparentColorPickerValue(
                                    category.color_code,
                                )
                                    ? undefined
                                    : colorPickerStyle(category.color_code)
                            }
                        >
                            {category.image_url ? (
                                <AvatarImage
                                    src={category.image_url}
                                    alt={category.name}
                                />
                            ) : null}
                            <AvatarFallback className="rounded-xl bg-transparent text-base">
                                {category.name.slice(0, 2).toUpperCase()}
                            </AvatarFallback>
                        </Avatar>
                        <div className="flex flex-col gap-2">
                            <Heading
                                title={category.name}
                                description={`/${category.slug}`}
                            />
                            <Badge
                                variant={
                                    category.status ? 'default' : 'secondary'
                                }
                            >
                                {category.status ? 'Active' : 'Inactive'}
                            </Badge>
                        </div>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button variant="default" asChild>
                            <Link href={CategoryController.index.url()}>
                                Back
                            </Link>
                        </Button>
                        {can('manage-catalog') ? (
                            <Button asChild>
                                <Link
                                    href={CategoryController.edit.url(
                                        category.slug,
                                    )}
                                >
                                    Edit
                                </Link>
                            </Button>
                        ) : null}
                    </div>
                </div>

                <Card>
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
                                label="Slug"
                                value={category.slug}
                            />
                        </dl>
                    </CardContent>
                </Card>
            </div>
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
