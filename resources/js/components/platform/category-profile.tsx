import { Link, usePage } from '@inertiajs/react';
import { format, isValid, parseISO } from 'date-fns';
import { Check, Copy, Pencil } from 'lucide-react';
import { useState, type ReactNode } from 'react';
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

function CountJewel({ label, count }: { label: string; count: number }) {
    return (
        <span className="inline-flex items-center gap-1.5">
            <span className="text-muted-foreground text-[11px] font-medium tracking-wide uppercase">
                {label}
            </span>
            <span className="bg-primary/10 text-primary inline-flex h-5 min-w-5 items-center justify-center rounded-md px-1.5 text-xs font-medium tabular-nums">
                {count}
            </span>
        </span>
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
    const { catalog } = usePage().props;
    const colorCode = normalizeCatalogColorPickerValue(category.color_code);
    const transparent = isTransparentColorPickerValue(colorCode);
    const children = category.children ?? [];
    const parent = category.parent;

    return (
        <div className="flex w-full flex-col gap-6 p-2 md:p-4">
            <div className="flex flex-wrap items-start justify-between gap-4">
                <div className="min-w-0 space-y-2">
                    <p className="text-muted-foreground text-xs font-medium tracking-wide uppercase">
                        Catalog category
                    </p>
                    <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">
                        {category.name}
                    </h1>
                    {category.slogan?.trim() ? (
                        <p className="text-muted-foreground max-w-2xl text-sm leading-relaxed">
                            {category.slogan}
                        </p>
                    ) : null}
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

            <Card className="shadow-sm">
                <CardHeader className="flex-row items-start justify-between gap-3">
                    <div className="min-w-0 space-y-1.5">
                        <CardTitle>Listing details</CardTitle>
                        <CardDescription>
                            How this category appears in the catalog and nav
                        </CardDescription>
                    </div>
                    <CountJewel
                        label="Products"
                        count={category.products_count}
                    />
                </CardHeader>
                <CardContent className="space-y-6">
                    <div className="grid items-start gap-6 lg:grid-cols-[14rem_minmax(0,1fr)] xl:grid-cols-[16rem_minmax(0,1fr)]">
                        <ProductGallery
                            card={false}
                            layout="tile"
                            productName={category.name}
                            primaryUrl={category.image_url}
                            images={[]}
                            colorCode={category.color_code}
                        />

                        <div className="min-w-0 space-y-5">
                            <dl className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
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
                                {catalog.show_parent_category ? (
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
                                ) : null}
                                <Fact
                                    label="Nav order"
                                    value={
                                        category.show_in_nav
                                            ? (category.nav_sort ?? '—')
                                            : 'Not in nav'
                                    }
                                />
                                <Fact
                                    label="Featured"
                                    value={
                                        <Badge
                                            variant={
                                                category.featured
                                                    ? 'outline'
                                                    : 'secondary'
                                            }
                                        >
                                            {category.featured ? 'Yes' : 'No'}
                                        </Badge>
                                    }
                                />
                                <Fact
                                    label="Status"
                                    value={
                                        <Badge
                                            variant={
                                                category.status
                                                    ? 'default'
                                                    : 'secondary'
                                            }
                                        >
                                            {category.status
                                                ? 'Active'
                                                : 'Inactive'}
                                        </Badge>
                                    }
                                />
                                <Fact
                                    label="Created"
                                    value={formatTimestamp(category.created_at)}
                                />
                                <Fact
                                    label="Updated"
                                    value={formatTimestamp(category.updated_at)}
                                />
                                <Fact
                                    label="Image background"
                                    value={
                                        <span className="inline-flex items-center gap-2">
                                            {transparent ? null : (
                                                <span
                                                    className="size-5 shrink-0 rounded-md border border-border/70"
                                                    style={colorPickerStyle(
                                                        colorCode,
                                                    )}
                                                    aria-hidden
                                                />
                                            )}
                                            {colorPickerSummary(colorCode)}
                                        </span>
                                    }
                                />
                            </dl>
                        </div>
                    </div>

                    {catalog.show_parent_category ? (
                        <div className="space-y-3 border-t pt-5">
                            <div className="flex items-center justify-between gap-3">
                                <p className="text-sm font-medium">
                                    Subcategories
                                </p>
                                <CountJewel
                                    label="Children"
                                    count={children.length}
                                />
                            </div>
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
                        </div>
                    ) : null}
                </CardContent>
            </Card>
        </div>
    );
}
