import { Link } from '@inertiajs/react';
import { Check, Copy } from 'lucide-react';
import { useState, type ReactNode } from 'react';
import { PRODUCT_TAG_OPTIONS } from '@/components/platform/product-form';
import { formatAppDateTime } from '@/lib/datetime';
import {
    ProductGallery,
    type ProductGalleryImage,
} from '@/components/product-gallery';
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

type ProductCategory = {
    id: number;
    name: string;
    slug: string;
};

type ProductSpecificationRow = {
    label: string;
    value: string;
};

type BranchProductSummaryProps = {
    name: string;
    sku: string | null;
    slug: string;
    brand?: string | null;
    packLabel?: string | null;
    piecesPerPack?: number | null;
    packUnit?: string | null;
    packSummary?: string | null;
    description?: string | null;
    status: boolean;
    imageUrl?: string | null;
    colorCode?: ColorPickerValue | null;
    gallery?: ProductGalleryImage[];
    categories?: ProductCategory[];
    categoryName: string;
    tags?: string[];
    specifications?: ProductSpecificationRow[];
    createdAt?: string | null;
    updatedAt?: string | null;
    createdByName?: string | null;
    categoryHref?: (slug: string) => string;
};

function tagLabel(value: string): string {
    return (
        PRODUCT_TAG_OPTIONS.find((option) => option.value === value)?.label ??
        value
    );
}

function formatTimestamp(value: string | null | undefined): string {
    if (!value) {
        return '—';
    }

    return formatAppDateTime(value) || '—';
}

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

export function BranchProductSummary({
    name,
    sku,
    slug,
    brand = null,
    packLabel = null,
    piecesPerPack = null,
    packUnit = null,
    packSummary = null,
    description,
    status,
    imageUrl = null,
    colorCode = null,
    gallery = [],
    categories = [],
    categoryName,
    tags = [],
    specifications = [],
    createdAt = null,
    updatedAt = null,
    createdByName = null,
    categoryHref,
}: BranchProductSummaryProps) {
    const normalizedColor = normalizeCatalogColorPickerValue(colorCode);
    const transparent = isTransparentColorPickerValue(normalizedColor);

    return (
        <Card className="gap-4 py-5 shadow-sm">
            <CardHeader className="px-5">
                <CardTitle>Listing details</CardTitle>
                <CardDescription>
                    What customers and operators see for this SKU
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-5 px-5">
                <div className="grid items-start gap-6 lg:grid-cols-[14rem_minmax(0,1fr)] xl:grid-cols-[16rem_minmax(0,1fr)]">
                    <ProductGallery
                        card={false}
                        layout="tile"
                        productName={name}
                        primaryUrl={imageUrl}
                        images={gallery}
                        colorCode={normalizedColor}
                    />

                    <div className="min-w-0 space-y-5">
                        <p className="text-sm leading-relaxed">
                            {description?.trim() || (
                                <span className="text-muted-foreground">
                                    No description yet.
                                </span>
                            )}
                        </p>

                        <dl className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                            <Fact
                                label="SKU"
                                value={
                                    <span className="font-mono">
                                        {sku ?? '—'}
                                    </span>
                                }
                                action={
                                    sku ? (
                                        <CopyValueButton
                                            value={sku}
                                            label="SKU"
                                        />
                                    ) : null
                                }
                            />
                            <Fact
                                label="Slug"
                                value={
                                    <span className="font-mono">/{slug}</span>
                                }
                                action={
                                    <CopyValueButton
                                        value={slug}
                                        label="slug"
                                    />
                                }
                            />
                            <Fact label="Brand" value={brand ?? '—'} />
                            <Fact
                                label="Pack summary"
                                value={packSummary ?? packLabel ?? '—'}
                            />
                            <Fact
                                label="Pieces per pack"
                                value={
                                    piecesPerPack !== null
                                        ? `${piecesPerPack}${
                                              packUnit ? ` ${packUnit}` : ''
                                          }`
                                        : '—'
                                }
                            />
                            <Fact
                                label="Status"
                                value={status ? 'Active' : 'Inactive'}
                            />
                            <Fact
                                label="Image background"
                                value={
                                    <span className="inline-flex items-center gap-2">
                                        {transparent ? null : (
                                            <span
                                                className="border-border/70 size-5 shrink-0 rounded-md border"
                                                style={colorPickerStyle(
                                                    normalizedColor,
                                                )}
                                                aria-hidden
                                            />
                                        )}
                                        {colorPickerSummary(normalizedColor)}
                                    </span>
                                }
                            />
                            <Fact
                                label="Updated"
                                value={formatTimestamp(updatedAt)}
                            />
                            <Fact
                                label="Created"
                                value={formatTimestamp(createdAt)}
                            />
                            <Fact
                                label="Created by"
                                value={createdByName ?? '—'}
                            />
                        </dl>

                        <div className="space-y-2">
                            <p className="text-muted-foreground text-[11px] font-medium tracking-wide uppercase">
                                Categories
                            </p>
                            {categories.length > 0 ? (
                                <div className="flex flex-wrap gap-1.5">
                                    {categories.map((category) => {
                                        const href = categoryHref?.(
                                            category.slug,
                                        );

                                        if (href) {
                                            return (
                                                <Badge
                                                    key={category.id}
                                                    variant="secondary"
                                                    asChild
                                                >
                                                    <Link href={href}>
                                                        {category.name}
                                                    </Link>
                                                </Badge>
                                            );
                                        }

                                        return (
                                            <Badge
                                                key={category.id}
                                                variant="secondary"
                                            >
                                                {category.name}
                                            </Badge>
                                        );
                                    })}
                                </div>
                            ) : (
                                <p className="text-sm">{categoryName || '—'}</p>
                            )}
                        </div>

                        <div className="space-y-2">
                            <p className="text-muted-foreground text-[11px] font-medium tracking-wide uppercase">
                                Merchandising tags
                            </p>
                            {tags.length > 0 ? (
                                <div className="flex flex-wrap gap-1.5">
                                    {tags.map((tag) => (
                                        <Badge key={tag} variant="outline">
                                            {tagLabel(tag)}
                                        </Badge>
                                    ))}
                                </div>
                            ) : (
                                <p className="text-muted-foreground text-sm">
                                    No storefront tags
                                </p>
                            )}
                        </div>
                    </div>
                </div>

                <div className="space-y-3 border-t pt-5">
                    <p className="text-muted-foreground text-[11px] font-medium tracking-wide uppercase">
                        Specifications
                    </p>
                    {specifications.length > 0 ? (
                        <div className="grid grid-cols-2 gap-2 sm:grid-cols-3 xl:grid-cols-4">
                            {specifications.map((row) => (
                                <div
                                    key={`${row.label}-${row.value}`}
                                    className="bg-muted/40 flex min-w-0 flex-col gap-1 rounded-lg px-3 py-2.5"
                                >
                                    <p className="text-muted-foreground truncate text-[11px] font-medium tracking-wide uppercase">
                                        {row.label}
                                    </p>
                                    <p className="text-sm leading-snug font-semibold wrap-break-word">
                                        {row.value}
                                    </p>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <p className="text-muted-foreground text-sm">
                            No specifications yet.
                        </p>
                    )}
                </div>
            </CardContent>
        </Card>
    );
}
