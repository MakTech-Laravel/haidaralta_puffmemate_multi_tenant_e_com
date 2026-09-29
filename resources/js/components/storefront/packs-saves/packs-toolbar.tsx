import { StorefrontSearchBar } from '@/components/storefront/storefront-search-bar';
import type { StorefrontSortOption } from '@/components/storefront/storefront-sort-select';
import { StorefrontSortSelect } from '@/components/storefront/storefront-sort-select';
import { cn } from '@/lib/utils';

export const packSortOptions: StorefrontSortOption[] = [
    { value: 'savings_desc', label: 'Highest savings' },
    { value: 'price_asc', label: 'Price: low to high' },
    { value: 'price_desc', label: 'Price: high to low' },
    { value: 'newest', label: 'Newest' },
];

type PacksToolbarProps = {
    search: string;
    sort: string | null;
    onSearchChange: (value: string) => void;
    onSearchSubmit: () => void;
    onSortChange: (value: string | null) => void;
    className?: string;
};

export function PacksToolbar({
    search,
    sort,
    onSearchChange,
    onSearchSubmit,
    onSortChange,
    className,
}: PacksToolbarProps) {
    return (
        <div
            className={cn(
                'flex w-full flex-col gap-3 lg:flex-row lg:items-center',
                className,
            )}
        >
            <StorefrontSearchBar
                value={search}
                onChange={onSearchChange}
                onSubmit={onSearchSubmit}
                className="lg:min-w-0 lg:flex-1"
            />
            <StorefrontSortSelect
                value={sort}
                onChange={onSortChange}
                options={packSortOptions}
                placeholder="Highest savings"
                className="lg:w-56 lg:max-w-xs lg:shrink-0"
            />
        </div>
    );
}
