import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowDown, ArrowUp, Pencil, Plus, Trash2 } from 'lucide-react';
import type { FormEvent } from 'react';
import { AppDateTime } from '@/components/app-datetime';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { cn } from '@/lib/utils';

type PriceRangeRow = {
    id: number;
    label: string;
    slug: string;
    min_amount: string | null;
    max_amount: string | null;
    sort_order: number;
    status: boolean;
    updated_at: string | null;
};

type SliderBounds = {
    slider_min: string;
    slider_max: string;
};

function formatBand(row: PriceRangeRow): string {
    if (row.min_amount === null && row.max_amount !== null) {
        return `Up to $${Number(row.max_amount).toFixed(2)}`;
    }

    if (row.min_amount !== null && row.max_amount === null) {
        return `From $${Number(row.min_amount).toFixed(2)} & above`;
    }

    if (row.min_amount !== null && row.max_amount !== null) {
        return `$${Number(row.min_amount).toFixed(2)} – $${Number(row.max_amount).toFixed(2)}`;
    }

    return 'Open';
}

export default function PlatformPriceRangesIndex({
    priceRanges,
    sliderBounds,
}: {
    priceRanges: PriceRangeRow[];
    sliderBounds: SliderBounds;
}) {
    const boundsForm = useForm({
        slider_min: sliderBounds.slider_min,
        slider_max: sliderBounds.slider_max,
    });

    const saveBounds = (event: FormEvent) => {
        event.preventDefault();
        boundsForm.put('/platform/price-ranges/bounds', {
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title="Shop price ranges" />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <Heading
                        title="Shop price ranges"
                        description="Preset bands shown in the storefront shop sidebar. Amounts are AUD catalog prices."
                    />
                    <Button asChild>
                        <Link href="/platform/price-ranges/create">
                            <Plus className="size-4" />
                            Add range
                        </Link>
                    </Button>
                </div>

                <form
                    onSubmit={saveBounds}
                    className="border-border/80 bg-card space-y-4 rounded-2xl border p-4 shadow-sm sm:p-5"
                >
                    <div className="space-y-1">
                        <h2 className="text-foreground text-base font-semibold">
                            Shop slider bounds
                        </h2>
                        <p className="text-muted-foreground text-sm">
                            Fixed min and max for the storefront price slider
                            and inputs (replaces “Any”).
                        </p>
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="slider_min">Min</Label>
                            <Input
                                id="slider_min"
                                type="number"
                                min={0}
                                step="0.01"
                                inputMode="decimal"
                                value={boundsForm.data.slider_min}
                                onChange={(event) =>
                                    boundsForm.setData(
                                        'slider_min',
                                        event.target.value,
                                    )
                                }
                                placeholder="0"
                            />
                            <InputError
                                message={boundsForm.errors.slider_min}
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="slider_max">Max</Label>
                            <Input
                                id="slider_max"
                                type="number"
                                min={0}
                                step="0.01"
                                inputMode="decimal"
                                value={boundsForm.data.slider_max}
                                onChange={(event) =>
                                    boundsForm.setData(
                                        'slider_max',
                                        event.target.value,
                                    )
                                }
                                placeholder="50000"
                            />
                            <InputError
                                message={boundsForm.errors.slider_max}
                            />
                        </div>
                    </div>
                    <Button type="submit" disabled={boundsForm.processing}>
                        Save slider bounds
                    </Button>
                </form>

                {priceRanges.length === 0 ? (
                    <div className="border-border/80 bg-card rounded-2xl border p-8 text-center shadow-sm">
                        <p className="text-muted-foreground text-sm">
                            No price ranges yet. Add presets for the shop filter.
                        </p>
                        <Button className="mt-4" asChild>
                            <Link href="/platform/price-ranges/create">
                                <Plus className="size-4" />
                                Add range
                            </Link>
                        </Button>
                    </div>
                ) : (
                    <div className="space-y-3">
                        {priceRanges.map((range, index) => (
                            <div
                                key={range.id}
                                className={cn(
                                    'border-border/80 bg-card flex flex-col gap-4 rounded-2xl border p-4 shadow-sm sm:flex-row sm:items-center',
                                )}
                            >
                                <div className="min-w-0 flex-1">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <p className="text-foreground font-semibold">
                                            {range.label}
                                        </p>
                                        <Badge variant="secondary">
                                            {formatBand(range)}
                                        </Badge>
                                        {!range.status ? (
                                            <Badge variant="outline">Hidden</Badge>
                                        ) : null}
                                    </div>
                                    <p className="text-muted-foreground mt-1 text-xs">
                                        {range.slug}
                                        {range.updated_at ? (
                                            <>
                                                {' · '}
                                                <AppDateTime
                                                    value={range.updated_at}
                                                />
                                            </>
                                        ) : null}
                                    </p>
                                </div>

                                <div className="flex flex-wrap items-center gap-2 sm:justify-end">
                                    <Switch
                                        checked={range.status}
                                        onCheckedChange={(checked) =>
                                            router.patch(
                                                `/platform/price-ranges/${range.id}/status`,
                                                { status: checked },
                                                { preserveScroll: true },
                                            )
                                        }
                                        aria-label={`Toggle ${range.label}`}
                                    />
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="icon"
                                        disabled={index === 0}
                                        onClick={() =>
                                            router.post(
                                                `/platform/price-ranges/${range.id}/move`,
                                                { direction: 'up' },
                                                { preserveScroll: true },
                                            )
                                        }
                                    >
                                        <ArrowUp className="size-4" />
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="icon"
                                        disabled={
                                            index === priceRanges.length - 1
                                        }
                                        onClick={() =>
                                            router.post(
                                                `/platform/price-ranges/${range.id}/move`,
                                                { direction: 'down' },
                                                { preserveScroll: true },
                                            )
                                        }
                                    >
                                        <ArrowDown className="size-4" />
                                    </Button>
                                    <Button variant="outline" size="icon" asChild>
                                        <Link
                                            href={`/platform/price-ranges/${range.id}/edit`}
                                        >
                                            <Pencil className="size-4" />
                                        </Link>
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="icon"
                                        onClick={() => {
                                            if (
                                                confirm(
                                                    `Remove “${range.label}”?`,
                                                )
                                            ) {
                                                router.delete(
                                                    `/platform/price-ranges/${range.id}`,
                                                    {
                                                        preserveScroll: true,
                                                    },
                                                );
                                            }
                                        }}
                                    >
                                        <Trash2 className="size-4" />
                                    </Button>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}
