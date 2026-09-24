import { Head, Link, router } from '@inertiajs/react';
import { ArrowDown, ArrowUp, Pencil, Plus, Trash2 } from 'lucide-react';
import { AppDateTime } from '@/components/app-datetime';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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

function formatBand(row: PriceRangeRow): string {
    if (row.min_amount === null && row.max_amount !== null) {
        return `Up to $${Number(row.max_amount).toFixed(2)}`;
    }

    if (row.min_amount !== null && row.max_amount === null) {
        return `From $${Number(row.min_amount).toFixed(2)}`;
    }

    if (row.min_amount !== null && row.max_amount !== null) {
        return `$${Number(row.min_amount).toFixed(2)} – $${Number(row.max_amount).toFixed(2)}`;
    }

    return 'Any';
}

export default function PlatformPriceRangesIndex({
    priceRanges,
}: {
    priceRanges: PriceRangeRow[];
}) {
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
