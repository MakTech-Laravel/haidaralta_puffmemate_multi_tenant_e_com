import { Search } from 'lucide-react';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';

type StorefrontSearchBarProps = {
    value: string;
    onChange: (value: string) => void;
    onSubmit?: () => void;
    className?: string;
    placeholder?: string;
};

export function StorefrontSearchBar({
    value,
    onChange,
    onSubmit,
    className,
    placeholder = 'Enter your favourite choice........',
}: StorefrontSearchBarProps) {
    return (
        <label
            className={cn(
                'border-input bg-card focus-within:border-ring focus-within:ring-ring/30 relative flex h-11 min-h-11 w-full items-center rounded-full border transition-[color,box-shadow] focus-within:ring-[3px]',
                className,
            )}
        >
            <Search
                className="text-muted-foreground pointer-events-none absolute left-4 size-4"
                aria-hidden
            />
            <Input
                type="search"
                value={value}
                onChange={(event) => onChange(event.target.value)}
                onKeyDown={(event) => {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        onSubmit?.();
                    }
                }}
                placeholder={placeholder}
                className="h-full rounded-full border-0 bg-transparent pr-4 pl-11 shadow-none focus-visible:ring-0"
                aria-label="Search products"
            />
        </label>
    );
}
