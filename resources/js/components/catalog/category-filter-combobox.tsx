import { useMemo } from 'react';
import {
    InfiniteScrollMultiCombobox,
    type MultiComboboxOption,
} from '@/components/stock/infinite-scroll-multi-combobox';
import { Label } from '@/components/ui/label';

type CategoryOption = { id: number; name: string; slug?: string };

function normalizeCategoryIds(
    value: string | number | boolean | string[] | number[] | null | undefined,
): string[] {
    if (
        value === null ||
        value === undefined ||
        value === '' ||
        value === 'all'
    ) {
        return [];
    }

    if (Array.isArray(value)) {
        return value.map(String).filter((id) => id !== '');
    }

    return [String(value)];
}

export function CategoryFilterCombobox({
    optionsUrl,
    value,
    selectedCategories,
    onChange,
}: {
    optionsUrl: string;
    value: string | number | boolean | string[] | number[] | null | undefined;
    selectedCategories?: CategoryOption[];
    onChange: (ids: string[] | null) => void;
}) {
    const values = useMemo(() => normalizeCategoryIds(value), [value]);

    const selectedItems = useMemo<MultiComboboxOption[]>(() => {
        const labels = new Map(
            (selectedCategories ?? []).map((category) => [
                String(category.id),
                category.name,
            ]),
        );

        return values.map((id) => ({
            value: id,
            label: labels.get(id) ?? `Category #${id}`,
        }));
    }, [selectedCategories, values]);

    return (
        <div className="grid min-w-56 gap-2">
            <Label>Category</Label>
            <InfiniteScrollMultiCombobox
                optionsUrl={optionsUrl}
                values={values}
                selectedItems={selectedItems}
                onToggle={(option, selected) => {
                    const next = selected
                        ? [...values, option.value]
                        : values.filter((id) => id !== option.value);

                    onChange(next.length > 0 ? next : null);
                }}
                placeholder="All categories"
                searchPlaceholder="Search categories…"
                emptyText="No categories found."
                descriptionKey={null}
                showSelectedBadges={false}
                className="w-64"
            />
        </div>
    );
}
