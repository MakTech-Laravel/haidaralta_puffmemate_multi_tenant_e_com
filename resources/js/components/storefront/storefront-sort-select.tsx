import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { cn } from '@/lib/utils';

export type StorefrontSortOption = {
    value: string;
    label: string;
};

export const storefrontSortOptions: StorefrontSortOption[] = [
    { value: 'name_asc', label: 'Name A–Z' },
    { value: 'name_desc', label: 'Name Z–A' },
    { value: 'newest', label: 'Newest' },
    { value: 'price_asc', label: 'Price: low to high' },
    { value: 'price_desc', label: 'Price: high to low' },
];

type StorefrontSortSelectProps = {
    value: string | null;
    onChange: (value: string | null) => void;
    className?: string;
    placeholder?: string;
    options?: StorefrontSortOption[];
};

export function StorefrontSortSelect({
    value,
    onChange,
    className,
    placeholder = 'Sort order',
    options = storefrontSortOptions,
}: StorefrontSortSelectProps) {
    return (
        <div className={cn('w-full', className)}>
            <Select
                value={value ?? undefined}
                onValueChange={(next) => onChange(next || null)}
            >
                <SelectTrigger
                    className="border-primary bg-background h-10 min-h-10 w-full rounded-none py-0 shadow-none data-[size=default]:h-10"
                    aria-label="Sort order"
                >
                    <SelectValue placeholder={placeholder} />
                </SelectTrigger>
                <SelectContent>
                    {options.map((option) => (
                        <SelectItem key={option.value} value={option.value}>
                            {option.label}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
        </div>
    );
}
