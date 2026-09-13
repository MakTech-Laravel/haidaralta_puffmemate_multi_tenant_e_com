import { cn } from '@/lib/utils';

export type ProductSectionTabId =
    | 'specifications'
    | 'shipping'
    | 'responsible'
    | 'reviews';

const tabs: Array<{ id: ProductSectionTabId; label: string }> = [
    { id: 'specifications', label: 'Specifications' },
    { id: 'shipping', label: 'Shipping & Delivery' },
    { id: 'responsible', label: 'Responsible Retail' },
    { id: 'reviews', label: 'Reviews' },
];

type ProductSectionTabsProps = {
    active: ProductSectionTabId;
    onChange: (tab: ProductSectionTabId) => void;
    reviewCount?: number;
    className?: string;
};

export function ProductSectionTabs({
    active,
    onChange,
    reviewCount,
    className,
}: ProductSectionTabsProps) {
    return (
        <div
            className={cn(
                'grid gap-2 sm:grid-cols-2 lg:grid-cols-4',
                className,
            )}
        >
            {tabs.map((tab) => {
                const isActive = tab.id === active;
                const label =
                    tab.id === 'reviews' && typeof reviewCount === 'number'
                        ? `${tab.label} (${reviewCount})`
                        : tab.label;

                return (
                    <button
                        key={tab.id}
                        type="button"
                        onClick={() => onChange(tab.id)}
                        className={cn(
                            'h-12 rounded-none border px-3 text-xs font-bold tracking-wide uppercase transition sm:text-sm',
                            isActive
                                ? 'border-primary bg-primary text-primary-foreground'
                                : 'border-border bg-background text-foreground hover:border-primary',
                        )}
                    >
                        {label}
                    </button>
                );
            })}
        </div>
    );
}
