import { useEffect, useRef, useState } from 'react';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

export type ShopSidebarFilters = {
    minPrice: number | null;
    maxPrice: number | null;
    categories: string[];
    tags: string[];
};

export type ShopCategoryOption = {
    slug: string;
    name: string;
};

export type ShopTagOption = {
    value: string;
    label: string;
};

export type ShopPriceRangeOption = {
    slug: string;
    label: string;
    min: number | null;
    max: number | null;
};

export type ShopFiltersSidebarProps = {
    value: ShopSidebarFilters;
    onChange: (next: Partial<ShopSidebarFilters>) => void;
    categories: ShopCategoryOption[];
    tags: ShopTagOption[];
    priceRanges: ShopPriceRangeOption[];
    sliderMax: number;
    className?: string;
};

function activePricePresetSlug(
    minPrice: number | null,
    maxPrice: number | null,
    priceRanges: ShopPriceRangeOption[],
): string {
    if (minPrice === null && maxPrice === null) {
        return 'all';
    }

    const match = priceRanges.find(
        (preset) => preset.min === minPrice && preset.max === maxPrice,
    );

    return match?.slug ?? 'custom';
}

function toggleValue(
    selected: string[],
    value: string,
    checked: boolean,
): string[] {
    if (checked) {
        return selected.includes(value) ? selected : [...selected, value];
    }

    return selected.filter((item) => item !== value);
}

export function ShopFiltersSidebar({
    value,
    onChange,
    categories,
    tags,
    priceRanges,
    sliderMax,
    className,
}: ShopFiltersSidebarProps) {
    const sliderCeiling = Math.max(1, sliderMax);
    const [minDraft, setMinDraft] = useState(
        value.minPrice === null ? '' : String(value.minPrice),
    );
    const [maxDraft, setMaxDraft] = useState(
        value.maxPrice === null ? '' : String(value.maxPrice),
    );
    const [sliderMin, setSliderMin] = useState(value.minPrice ?? 0);
    const [sliderMaxValue, setSliderMaxValue] = useState(
        value.maxPrice ?? sliderCeiling,
    );
    const [isSliding, setIsSliding] = useState(false);
    const sliderMinRef = useRef(sliderMin);
    const sliderMaxRef = useRef(sliderMaxValue);

    useEffect(() => {
        setMinDraft(value.minPrice === null ? '' : String(value.minPrice));
        setMaxDraft(value.maxPrice === null ? '' : String(value.maxPrice));

        if (!isSliding) {
            const nextMin = value.minPrice ?? 0;
            const nextMax = value.maxPrice ?? sliderCeiling;
            setSliderMin(nextMin);
            setSliderMaxValue(nextMax);
            sliderMinRef.current = nextMin;
            sliderMaxRef.current = nextMax;
        }
    }, [value.minPrice, value.maxPrice, isSliding, sliderCeiling]);

    const presetSlug = activePricePresetSlug(
        value.minPrice,
        value.maxPrice,
        priceRanges,
    );
    const allCategories = value.categories.length === 0;
    const allTags = value.tags.length === 0;

    const commitPrice = (min: number, max: number) => {
        onChange({
            minPrice: min <= 0 ? null : min,
            maxPrice: max >= sliderCeiling ? null : max,
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
                            left: `${(sliderMin / sliderCeiling) * 100}%`,
                            right: `${100 - (sliderMaxValue / sliderCeiling) * 100}%`,
                        }}
                    />
                    <input
                        type="range"
                        min={0}
                        max={sliderCeiling}
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
                        min={0}
                        max={sliderCeiling}
                        step={5}
                        value={sliderMaxValue}
                        aria-label="Maximum price"
                        onPointerDown={() => setIsSliding(true)}
                        onChange={(event) => {
                            const nextMax = Math.max(
                                Number(event.target.value),
                                sliderMinRef.current,
                            );
                            setIsSliding(true);
                            setSliderMaxValue(nextMax);
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
                    <li>
                        <button
                            type="button"
                            onClick={() =>
                                onChange({
                                    minPrice: null,
                                    maxPrice: null,
                                })
                            }
                            className="flex w-full items-center gap-2.5 text-left text-sm"
                        >
                            <span
                                className={cn(
                                    'flex size-4 shrink-0 items-center justify-center rounded-full border',
                                    presetSlug === 'all'
                                        ? 'border-primary'
                                        : 'border-border',
                                )}
                                aria-hidden
                            >
                                {presetSlug === 'all' ? (
                                    <span className="bg-primary size-2 rounded-full" />
                                ) : null}
                            </span>
                            <span
                                className={cn(
                                    presetSlug === 'all'
                                        ? 'text-foreground font-medium'
                                        : 'text-muted-foreground',
                                )}
                            >
                                All Price
                            </span>
                        </button>
                    </li>
                    {priceRanges.map((preset) => {
                        const selected = presetSlug === preset.slug;

                        return (
                            <li key={preset.slug}>
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
                    {categories.map((option) => {
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
                                            categories: toggleValue(
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
                                    {option.name}
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
                            checked={allTags}
                            onCheckedChange={() => onChange({ tags: [] })}
                        />
                        <Label
                            htmlFor="shop-type-all"
                            className="text-foreground cursor-pointer text-sm font-normal"
                        >
                            All
                        </Label>
                    </li>
                    {tags.map((option) => {
                        const checked = value.tags.includes(option.value);

                        return (
                            <li
                                key={option.value}
                                className="flex items-center gap-2.5"
                            >
                                <Checkbox
                                    id={`shop-type-${option.value}`}
                                    checked={checked}
                                    onCheckedChange={(state) =>
                                        onChange({
                                            tags: toggleValue(
                                                value.tags,
                                                option.value,
                                                state === true,
                                            ),
                                        })
                                    }
                                />
                                <Label
                                    htmlFor={`shop-type-${option.value}`}
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
