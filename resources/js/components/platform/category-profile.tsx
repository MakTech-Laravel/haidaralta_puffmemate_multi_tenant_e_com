import { Link } from '@inertiajs/react';
import { format, isValid, parseISO } from 'date-fns';
import {
    Check,
    Copy,
    FolderTree,
    ImageIcon,
    Package,
    Pencil,
} from 'lucide-react';
import { useState, type ReactNode } from 'react';
import { COLOR_PICKER_CHECKERBOARD } from '@/components/color-picker/color-swatch-popover';
import { DemoStatCard } from '@/components/portal/demo-stat-card';
import { ProductGallery } from '@/components/product-gallery';
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

export type CategoryProfileChild = {
    id: number;
    name: string;
    slug: string;
    status: boolean;
};

export type CategoryProfileParent = {
    id: number;
    name: string;
    slug: string;
};

export type CategoryProfileRecord = {
    id: number;
    name: string;
    slug: string;
    parent_name: string | null;
    parent?: CategoryProfileParent | null;
    image_url: string | null;
    color_code?: ColorPickerValue | null;
    status: boolean;
    featured: boolean;
    slogan?: string | null;
    show_in_nav?: boolean;
    nav_sort?: number | null;
    products_count: number;
    children_count: number;
    children?: CategoryProfileChild[];
    created_at: string | null;
    updated_at: string | null;
};

function Fact({
    label,
    value,
    action,
}: {
    label: string;
    value: ReactNode;
    action?: ReactNode;
}) {
    return (
        <div className="flex min-w-0 flex-col gap-1">
            <dt className="text-muted-foreground text-[11px] font-medium tracking-wide uppercase">
                {label}
            </dt>
            <dd className="flex items-start justify-between gap-2 text-sm font-medium">
                <span className="min-w-0 wrap-break-word">{value}</span>
                {action}
            </dd>
        </div>
    );
}

function CopyValueButton({ value, label }: { value: string; label: string }) {
    const [copied, setCopied] = useState(false);

    return (
        <Button
            type="button"
            variant="ghost"
            size="icon"
            className="size-7 shrink-0"
            aria-label={`Copy ${label}`}
            onClick={() => {
                void navigator.clipboard.writeText(value).then(() => {
                    setCopied(true);
                    window.setTimeout(() => setCopied(false), 1600);
                });
            }}
        >
            {copied ? (
                <Check className="size-3.5" />
            ) : (
                <Copy className="size-3.5" />
            )}
        </Button>
    );
}

function formatTimestamp(value: string | null): string {
    if (!value) {
        return '—';
    }

    const parsed = parseISO(value);

    return isValid(parsed) ? format(parsed, 'd MMM yyyy, HH:mm') : value;
}

export function CategoryProfile({
    category,
    indexHref,
    editHref,
    parentHref,
    childHref,
}: {
    category: CategoryProfileRecord;
    indexHref: string;
    editHref?: string | null;
    parentHref?: string | null;
    childHref: (slug: string) => string;
}) {
    const colorCode = normalizeCatalogColorPickerValue(category.color_code);
    const transparent = isTransparentColorPickerValue(colorCode);
    const children = category.children ?? [];
    const parent = category.parent;

    return (
        <div className="flex w-full flex-col gap-6 p-2 md:p-4">
            <div className="flex flex-wrap items-start justify-between gap-4">
                <div className="min-w-0 space-y-3">
                    <p className="text-muted-foreground text-xs font-medium tracking-wide uppercase">
                        Catalog category
                    </p>
                    <div className="space-y-2">
                        <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">
                            {category.name}
                        </h1>
                        {category.slogan?.trim() ? (
                            <p className="text-muted-foreground max-w-2xl text-sm leading-relaxed">
                                {category.slogan}
                            </p>
                        ) : null}
                        <div className="flex flex-wrap items-center gap-1.5">
                            <Badge
                                variant={
                                    category.status ? 'default' : 'secondary'
                                }
                            >
                                {category.status
                                    ? 'Active in catalog'
                                    : 'Inactive'}
                            </Badge>
                            {category.featured ? (
                                <Badge variant="outline">Featured</Badge>
                            ) : null}
                            {category.show_in_nav ? (
                                <Badge variant="outline">In navigation</Badge>
                            ) : null}
                        </div>
                    </div>
                </div>
                <div className="flex flex-wrap gap-2">
                    <Button variant="outline" asChild>
                        <Link href={indexHref}>All categories</Link>
                    </Button>
                    {editHref ? (
                        <Button asChild>
                            <Link href={editHref}>
                                <Pencil className="size-4" />
                                Edit
                            </Link>
                        </Button>
                    ) : null}
                </div>
            </div>

            <div className="grid items-start gap-6 xl:grid-cols-[minmax(0,1.15fr)_minmax(0,0.85fr)]">
                <ProductGallery
                    layout="hero"
                    productName={category.name}
                    primaryUrl={category.image_url}
                    images={[]}
                    colorCode={category.color_code}
                />

                <Card className="shadow-sm">
                    <CardHeader>
                        <CardTitle>Listing details</CardTitle>
                        <CardDescription>
                            How this category appears in the catalog and nav
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-5">
                        <dl className="grid gap-4 sm:grid-cols-2">
                            <Fact
                                label="Slug"
                                value={
                                    <span className="font-mono">
                                        /{category.slug}
                                    </span>
                                }
                                action={
                                    <CopyValueButton
                                        value={category.slug}
                                        label="slug"
                                    />
                                }
                            />
                            <Fact
                                label="Catalog ID"
                                value={
                                    <span className="font-mono">
                                        #{category.id}
                                    </span>
                                }
                            />
                            <Fact
                                label="Parent"
                                value={
                                    parent && parentHref ? (
                                        <Link
                                            href={parentHref}
                                            className="hover:text-primary underline-offset-4 hover:underline"
                                        >
                                            {parent.name}
                                        </Link>
                                    ) : (
                                        (category.parent_name ?? 'None')
                                    )
                                }
                            />
                            <Fact
                                label="Nav order"
                                value={
                                    category.show_in_nav
                                        ? (category.nav_sort ?? '—')
                                        : 'Not in nav'
                                }
                            />
                        </dl>

                        <div className="flex items-center justify-between gap-3 rounded-xl border px-3 py-3">
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
                                    'size-10 shrink-0 rounded-lg border border-border/70 shadow-inner',
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

            <div className="grid gap-3 sm:grid-cols-3">
                <DemoStatCard
                    icon={Package}
                    label="Products"
                    value={String(category.products_count)}
                    hint="Assigned to this category"
                />
                <DemoStatCard
                    icon={FolderTree}
                    label="Subcategories"
                    value={String(category.children_count)}
                    hint="Direct children"
                />
                <DemoStatCard
                    icon={ImageIcon}
                    label="Media"
                    value={category.image_url ? '1' : '0'}
                    hint={
                        category.image_url
                            ? 'Listing photo set'
                            : 'Listing photo missing'
                    }
                />
            </div>

            <div className="grid items-start gap-6 xl:grid-cols-2">
                <Card className="shadow-sm">
                    <CardHeader>
                        <CardTitle>Subcategories</CardTitle>
                        <CardDescription>
                            Direct children in this catalog tree
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {children.length > 0 ? (
                            <div className="flex flex-wrap gap-1.5">
                                {children.map((child) => (
                                    <Badge
                                        key={child.id}
                                        variant={
                                            child.status
                                                ? 'secondary'
                                                : 'outline'
                                        }
                                        asChild
                                    >
                                        <Link href={childHref(child.slug)}>
                                            {child.name}
                                        </Link>
                                    </Badge>
                                ))}
                            </div>
                        ) : (
                            <p className="text-muted-foreground text-sm">
                                No subcategories yet.
                            </p>
                        )}
                    </CardContent>
                </Card>

                <Card className="shadow-sm">
                    <CardHeader>
                        <CardTitle>Catalog record</CardTitle>
                        <CardDescription>
                            Internal metadata for this category
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <dl className="grid gap-4 sm:grid-cols-2">
                            <Fact
                                label="Created"
                                value={formatTimestamp(category.created_at)}
                            />
                            <Fact
                                label="Updated"
                                value={formatTimestamp(category.updated_at)}
                            />
                            <Fact
                                label="Featured"
                                value={category.featured ? 'Yes' : 'No'}
                            />
                            <Fact
                                label="Status"
                                value={
                                    category.status ? 'Active' : 'Inactive'
                                }
                            />
                        </dl>
                    </CardContent>
                </Card>
            </div>
        </div>
    );
}
