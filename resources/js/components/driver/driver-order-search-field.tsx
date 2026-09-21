import { Search } from 'lucide-react';
import type { FormEvent } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

type DriverOrderSearchFieldProps = {
    value: string;
    onChange: (value: string) => void;
    onSubmit: (event: FormEvent<HTMLFormElement>) => void;
};

export function DriverOrderSearchField({
    value,
    onChange,
    onSubmit,
}: DriverOrderSearchFieldProps) {
    return (
        <form onSubmit={onSubmit} className="flex items-center gap-2">
            <div className="relative min-w-0 flex-1">
                <Search
                    className="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2"
                    aria-hidden
                />
                <Input
                    value={value}
                    onChange={(event) => onChange(event.target.value)}
                    placeholder="Search orders, names, or addresses"
                    aria-label="Search orders"
                    className="h-11 pl-10"
                />
            </div>
            <Button type="submit" variant="outline" className="h-11 shrink-0">
                Search
            </Button>
        </form>
    );
}
