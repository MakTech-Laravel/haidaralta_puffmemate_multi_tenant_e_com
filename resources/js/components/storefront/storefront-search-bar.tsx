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
                'border-primary bg-background relative flex h-12 min-h-12 w-full items-center rounded-none border',
                className,
            )}
        >
            <Search
                className="text-muted-foreground pointer-events-none absolute left-3 size-4"
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
                className="h-full rounded-none border-0 bg-transparent pr-3 pl-10 shadow-none focus-visible:ring-0"
                aria-label="Search products"
            />
        </label>
    );
}
