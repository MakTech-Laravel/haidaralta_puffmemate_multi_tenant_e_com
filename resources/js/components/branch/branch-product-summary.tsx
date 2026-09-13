import type { ReactNode } from 'react';
import { COLOR_PICKER_CHECKERBOARD } from '@/components/color-picker/color-swatch-popover';
import {
    ProductGallery,
    type ProductGalleryImage,
} from '@/components/product-gallery';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Switch } from '@/components/ui/switch';
import {
    colorPickerStyle,
    isTransparentColorPickerValue,
    normalizeCatalogColorPickerValue,
    type ColorPickerValue,
} from '@/lib/color-picker';
import { cn } from '@/lib/utils';

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

function categoryLabels(categoryName: string): string[] {
    return categoryName
        .split(',')
        .map((name) => name.trim())
        .filter(Boolean);
}

type BranchProductSummaryProps = {
    name: string;
    sku: string | null;
    description?: string | null;
    categoryName: string;
    imageUrl?: string | null;
    colorCode?: ColorPickerValue | null;
    gallery?: ProductGalleryImage[];
    enabled: boolean;
    lowStock?: boolean;
    onEnabledChange: (enabled: boolean) => void;
    enabledLabel?: string;
};

export function BranchProductSummary({
    name,
    sku,
    description,
    categoryName,
    imageUrl = null,
    colorCode = null,
    gallery = [],
    enabled,
    lowStock = false,
    onEnabledChange,
    enabledLabel = 'Available at this branch',
}: BranchProductSummaryProps) {
    const categories = categoryLabels(categoryName);
    const normalizedColor = normalizeCatalogColorPickerValue(colorCode);
    const transparent = isTransparentColorPickerValue(normalizedColor);

    return (
        <div className="space-y-6">
            <div className="grid items-stretch gap-6 xl:grid-cols-[minmax(0,1fr)_20rem]">
                <Card className="h-full gap-4 py-5 shadow-none">
                    <CardHeader className="px-5">
                        <CardTitle className="text-base">
                            Product details
                        </CardTitle>
                        <CardDescription>
                            Catalog information for this product
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="px-5">
                        <div className="flex flex-col gap-5 sm:flex-row sm:items-start">
                            <Avatar
                                className={cn(
                                    'size-20 rounded-xl border border-border/70',
                                    transparent && COLOR_PICKER_CHECKERBOARD,
                                )}
                                style={
                                    transparent
                                        ? undefined
                                        : colorPickerStyle(normalizedColor)
                                }
                            >
                                {imageUrl ? (
                                    <AvatarImage
                                        src={imageUrl}
                                        alt={name}
                                        className="object-contain p-1"
                                    />
                                ) : null}
                                <AvatarFallback className="rounded-xl bg-transparent text-base">
                                    {name.slice(0, 2).toUpperCase()}
                                </AvatarFallback>
                            </Avatar>
                            <dl className="grid min-w-0 flex-1 gap-5 sm:grid-cols-2">
                                <DetailItem label="SKU" value={sku ?? '—'} />
                                <DetailItem
                                    label="Categories"
                                    value={
                                        categories.length > 0 ? (
                                            <div className="flex flex-wrap gap-1.5">
                                                {categories.map((category) => (
                                                    <Badge
                                                        key={category}
                                                        variant="secondary"
                                                    >
                                                        {category}
                                                    </Badge>
                                                ))}
                                            </div>
                                        ) : (
                                            '—'
                                        )
                                    }
                                />
                                <div className="sm:col-span-2">
                                    <DetailItem
                                        label="Description"
                                        value={
                                            <span className="whitespace-pre-wrap">
                                                {description?.trim() || '—'}
                                            </span>
                                        }
                                    />
                                </div>
                            </dl>
                        </div>
                    </CardContent>
                </Card>

                <Card className="h-full gap-4 py-5 shadow-none">
                    <CardHeader className="px-5">
                        <CardTitle className="text-base">
                            Branch availability
                        </CardTitle>
                        <CardDescription>
                            Control whether this product sells here
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="flex flex-1 flex-col gap-5 px-5">
                        <div className="flex items-center justify-between gap-4">
                            <div className="space-y-1">
                                <Label
                                    htmlFor="branch-product-enabled"
                                    className="text-sm font-medium"
                                >
                                    {enabledLabel}
                                </Label>
                                <p className="text-muted-foreground text-xs">
                                    {enabled
                                        ? 'Visible and sellable at this branch.'
                                        : 'Hidden from this branch storefront.'}
                                </p>
                            </div>
                            <Switch
                                id="branch-product-enabled"
                                checked={enabled}
                                onCheckedChange={(checked) =>
                                    onEnabledChange(checked === true)
                                }
                            />
                        </div>

                        <Separator />

                        <div className="space-y-1">
                            <p className="text-muted-foreground text-sm">
                                Stock alert
                            </p>
                            {lowStock ? (
                                <Badge
                                    variant="outline"
                                    className="border-destructive/40 text-destructive"
                                >
                                    Low stock
                                </Badge>
                            ) : (
                                <p className="text-sm font-medium">Healthy</p>
                            )}
                        </div>
                    </CardContent>
                </Card>
            </div>

            <ProductGallery
                productName={name}
                images={gallery}
                colorCode={normalizedColor}
            />
        </div>
    );
}
