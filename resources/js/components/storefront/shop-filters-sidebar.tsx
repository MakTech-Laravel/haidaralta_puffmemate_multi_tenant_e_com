import { useEffect, useRef, useState } from 'react';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

export type ShopSidebarFilters = {
    minPrice: number | null;
    maxPrice: number | null;
    categories: string[];
    types: string[];
};

export type ShopFiltersSidebarProps = {
    value: ShopSidebarFilters;
    onChange: (next: Partial<ShopSidebarFilters>) => void;
    className?: string;
};

const PRICE_SLIDER_MIN = 0;
const PRICE_SLIDER_MAX = 10_000;

type PricePreset = {
    id: string;
    label: string;
    min: number | null;
    max: number | null;
};

const PRICE_PRESETS: PricePreset[] = [
    { id: 'all', label: 'All Price', min: null, max: null },
    { id: 'under-20', label: 'Under $20', min: null, max: 20 },
    { id: '25-100', label: '$25 to $100', min: 25, max: 100 },
    { id: '100-300', label: '$100 to $300', min: 100, max: 300 },
    { id: '300-500', label: '$300 to $500', min: 300, max: 500 },
    { id: '500-1000', label: '$500 to $1,000', min: 500, max: 1_000 },
    { id: '1000-10000', label: '$1,000 to $10,000', min: 1_000, max: 10_000 },
];

export const SHOP_CATEGORY_OPTIONS = [
    { slug: 'cigarettes', label: 'Cigarettes' },
    { slug: 'vapes', label: 'Vapes' },
    { slug: 'tobacco', label: 'Tobacco' },
    { slug: 'nicotine-pouches', label: 'Nicotine Pouches' },
    { slug: 'accessories', label: 'Accessories' },
] as const;

export const SHOP_TYPE_OPTIONS = [
    { slug: 'best-selling', label: 'Best Selling Products' },
    { slug: 'new-arrival', label: 'New Arrival' },
] as const;

function activePricePresetId(
    minPrice: number | null,
    maxPrice: number | null,
): string {
    const match = PRICE_PRESETS.find(
        (preset) => preset.min === minPrice && preset.max === maxPrice,
    );

    return match?.id ?? 'custom';
}

function toggleSlug(
    selected: string[],
    slug: string,
    checked: boolean,
): string[] {
    if (checked) {
        return selected.includes(slug) ? selected : [...selected, slug];
    }

    return selected.filter((item) => item !== slug);
}

export function ShopFiltersSidebar({
    value,
    onChange,
    className,
}: ShopFiltersSidebarProps) {
    const [minDraft, setMinDraft] = useState(
        value.minPrice === null ? '' : String(value.minPrice),
    );
    const [maxDraft, setMaxDraft] = useState(
        value.maxPrice === null ? '' : String(value.maxPrice),
    );
    const [sliderMin, setSliderMin] = useState(
        value.minPrice ?? PRICE_SLIDER_MIN,
    );
    const [sliderMax, setSliderMax] = useState(
        value.maxPrice ?? PRICE_SLIDER_MAX,
    );
    const [isSliding, setIsSliding] = useState(false);
    const sliderMinRef = useRef(sliderMin);
    const sliderMaxRef = useRef(sliderMax);

    useEffect(() => {
        setMinDraft(value.minPrice === null ? '' : String(value.minPrice));
        setMaxDraft(value.maxPrice === null ? '' : String(value.maxPrice));

        if (!isSliding) {
            const nextMin = value.minPrice ?? PRICE_SLIDER_MIN;
            const nextMax = value.maxPrice ?? PRICE_SLIDER_MAX;
            setSliderMin(nextMin);
            setSliderMax(nextMax);
            sliderMinRef.current = nextMin;
            sliderMaxRef.current = nextMax;
        }
    }, [value.minPrice, value.maxPrice, isSliding]);

    const presetId = activePricePresetId(value.minPrice, value.maxPrice);
    const allCategories = value.categories.length === 0;
    const allTypes = value.types.length === 0;

    const commitPrice = (min: number, max: number) => {
        onChange({
            minPrice: min <= PRICE_SLIDER_MIN ? null : min,
            maxPrice: max >= PRICE_SLIDER_MAX ? null : max,
        });
    };

    const commitPriceDrafts = () => {
        const nextMin = minDraft === '' ? null : Number(minDraft);
        const nextMax = maxDraft === '' ? null : Number(maxDraft);

        onChange({
            minPrice:
                nextMin !== null && Number.isFinite(nextMin) ? nextMin : null,
            maxPrice:
                nextMax !== null && Number.isFinite(nextMax) ? nextMax : null,
        });
    };

    const finishSliding = () => {
        setIsSliding(false);
        commitPrice(sliderMinRef.current, sliderMaxRef.current);
    };

    return (
        <aside className={cn('flex flex-col gap-8', className)}>
            <section className="space-y-4">
                <h2 className="text-foreground text-base font-semibold">
                    Price Range
                </h2>

                <div className="relative h-6">
                    <div className="bg-muted absolute top-1/2 right-0 left-0 h-1 -translate-y-1/2 rounded-full" />
                    <div
                        className="bg-primary absolute top-1/2 h-1 -translate-y-1/2 rounded-full"
                        style={{
                            left: `${(sliderMin / PRICE_SLIDER_MAX) * 100}%`,
                            right: `${100 - (sliderMax / PRICE_SLIDER_MAX) * 100}%`,
                        }}
                    />
                    <input
                        type="range"
                        min={PRICE_SLIDER_MIN}
                        max={PRICE_SLIDER_MAX}
                        step={5}
                        value={sliderMin}
                        aria-label="Minimum price"
                        onPointerDown={() => setIsSliding(true)}
                        onChange={(event) => {
                            const nextMin = Math.min(
                                Number(event.target.value),
                                sliderMaxRef.current,
                            );
                            setIsSliding(true);
                            setSliderMin(nextMin);
                            sliderMinRef.current = nextMin;
                            setMinDraft(String(nextMin));
                        }}
                        onPointerUp={finishSliding}
                        onKeyUp={finishSliding}
                        className="[&::-webkit-slider-thumb]:border-primary [&::-webkit-slider-thumb]:bg-background [&::-moz-range-thumb]:border-primary [&::-moz-range-thumb]:bg-background pointer-events-none absolute inset-0 z-10 h-6 w-full appearance-none bg-transparent [&::-moz-range-thumb]:pointer-events-auto [&::-moz-range-thumb]:size-4 [&::-moz-range-thumb]:rounded-full [&::-moz-range-thumb]:border-2 [&::-webkit-slider-thumb]:pointer-events-auto [&::-webkit-slider-thumb]:size-4 [&::-webkit-slider-thumb]:appearance-none [&::-webkit-slider-thumb]:rounded-full [&::-webkit-slider-thumb]:border-2"
                    />
                    <input
                        type="range"
                        min={PRICE_SLIDER_MIN}
                        max={PRICE_SLIDER_MAX}
                        step={5}
                        value={sliderMax}
                        aria-label="Maximum price"
                        onPointerDown={() => setIsSliding(true)}
                        onChange={(event) => {
                            const nextMax = Math.max(
                                Number(event.target.value),
                                sliderMinRef.current,
                            );
                            setIsSliding(true);
                            setSliderMax(nextMax);
                            sliderMaxRef.current = nextMax;
                            setMaxDraft(String(nextMax));
                        }}
                        onPointerUp={finishSliding}
                        onKeyUp={finishSliding}
                        className="[&::-webkit-slider-thumb]:border-primary [&::-webkit-slider-thumb]:bg-background [&::-moz-range-thumb]:border-primary [&::-moz-range-thumb]:bg-background pointer-events-none absolute inset-0 z-20 h-6 w-full appearance-none bg-transparent [&::-moz-range-thumb]:pointer-events-auto [&::-moz-range-thumb]:size-4 [&::-moz-range-thumb]:rounded-full [&::-moz-range-thumb]:border-2 [&::-webkit-slider-thumb]:pointer-events-auto [&::-webkit-slider-thumb]:size-4 [&::-webkit-slider-thumb]:appearance-none [&::-webkit-slider-thumb]:rounded-full [&::-webkit-slider-thumb]:border-2"
                    />
                </div>

                <div className="grid grid-cols-2 gap-3">
                    <div className="space-y-1.5">
                        <Label
                            htmlFor="shop-min-price"
                            className="text-muted-foreground text-xs"
                        >
                            Min price
                        </Label>
                        <Input
                            id="shop-min-price"
                            type="number"
                            min={0}
                            inputMode="numeric"
                            placeholder="0"
                            value={minDraft}
                            onChange={(event) =>
                                setMinDraft(event.target.value)
                            }
                            onBlur={commitPriceDrafts}
                            onKeyDown={(event) => {
                                if (event.key === 'Enter') {
                                    commitPriceDrafts();
                                }
                            }}
                            className="h-9"
                        />
                    </div>
                    <div className="space-y-1.5">
                        <Label
                            htmlFor="shop-max-price"
                            className="text-muted-foreground text-xs"
                        >
                            Max price
                        </Label>
                        <Input
                            id="shop-max-price"
                            type="number"
                            min={0}
                            inputMode="numeric"
                            placeholder="Any"
                            value={maxDraft}
                            onChange={(event) =>
                                setMaxDraft(event.target.value)
                            }
                            onBlur={commitPriceDrafts}
                            onKeyDown={(event) => {
                                if (event.key === 'Enter') {
                                    commitPriceDrafts();
                                }
                            }}
                            className="h-9"
                        />
                    </div>
                </div>

                <ul className="space-y-2.5">
                    {PRICE_PRESETS.map((preset) => {
                        const selected = presetId === preset.id;

                        return (
                            <li key={preset.id}>
                                <button
                                    type="button"
                                    onClick={() =>
                                        onChange({
                                            minPrice: preset.min,
                                            maxPrice: preset.max,
                                        })
                                    }
                                    className="flex w-full items-center gap-2.5 text-left text-sm"
                                >
                                    <span
                                        className={cn(
                                            'flex size-4 shrink-0 items-center justify-center rounded-full border',
                                            selected
                                                ? 'border-primary'
                                                : 'border-border',
                                        )}
                                        aria-hidden
                                    >
                                        {selected ? (
                                            <span className="bg-primary size-2 rounded-full" />
                                        ) : null}
                                    </span>
                                    <span
                                        className={cn(
                                            selected
                                                ? 'text-foreground font-medium'
                                                : 'text-muted-foreground',
                                        )}
                                    >
                                        {preset.label}
                                    </span>
                                </button>
                            </li>
                        );
                    })}
                </ul>
            </section>

            <section className="space-y-4">
                <h2 className="text-foreground text-base font-semibold">
                    Category
                </h2>
                <ul className="space-y-2.5">
                    <li className="flex items-center gap-2.5">
                        <Checkbox
                            id="shop-category-all"
                            checked={allCategories}
                            onCheckedChange={() => onChange({ categories: [] })}
                        />
                        <Label
                            htmlFor="shop-category-all"
                            className="text-foreground cursor-pointer text-sm font-normal"
                        >
                            All
                        </Label>
                    </li>
                    {SHOP_CATEGORY_OPTIONS.map((option) => {
                        const checked = value.categories.includes(option.slug);

                        return (
                            <li
                                key={option.slug}
                                className="flex items-center gap-2.5"
                            >
                                <Checkbox
                                    id={`shop-category-${option.slug}`}
                                    checked={checked}
                                    onCheckedChange={(state) =>
                                        onChange({
                                            categories: toggleSlug(
                                                value.categories,
                                                option.slug,
                                                state === true,
                                            ),
                                        })
                                    }
                                />
                                <Label
                                    htmlFor={`shop-category-${option.slug}`}
                                    className="text-foreground cursor-pointer text-sm font-normal"
                                >
                                    {option.label}
                                </Label>
                            </li>
                        );
                    })}
                </ul>
            </section>

            <section className="space-y-4">
                <h2 className="text-foreground text-base font-semibold">
                    Type
                </h2>
                <ul className="space-y-2.5">
                    <li className="flex items-center gap-2.5">
                        <Checkbox
                            id="shop-type-all"
                            checked={allTypes}
                            onCheckedChange={() => onChange({ types: [] })}
                        />
                        <Label
                            htmlFor="shop-type-all"
                            className="text-foreground cursor-pointer text-sm font-normal"
                        >
                            All
                        </Label>
                    </li>
                    {SHOP_TYPE_OPTIONS.map((option) => {
                        const checked = value.types.includes(option.slug);

                        return (
                            <li
                                key={option.slug}
                                className="flex items-center gap-2.5"
                            >
                                <Checkbox
                                    id={`shop-type-${option.slug}`}
                                    checked={checked}
                                    onCheckedChange={(state) =>
                                        onChange({
                                            types: toggleSlug(
                                                value.types,
                                                option.slug,
                                                state === true,
                                            ),
                                        })
                                    }
                                />
                                <Label
                                    htmlFor={`shop-type-${option.slug}`}
                                    className="text-foreground cursor-pointer text-sm font-normal"
                                >
                                    {option.label}
                                </Label>
                            </li>
                        );
                    })}
                </ul>
            </section>
        </aside>
    );
}
