import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { cn } from '@/lib/utils';

export type StorefrontBranchOption = {
    id: string;
    name: string;
};

type StorefrontBranchSelectProps = {
    branches: StorefrontBranchOption[];
    value: string | null;
    onChange: (value: string | null) => void;
    className?: string;
    placeholder?: string;
};

export function StorefrontBranchSelect({
    branches,
    value,
    onChange,
    className,
    placeholder = 'Select Your Nearest Branch',
}: StorefrontBranchSelectProps) {
    return (
        <div className={cn('w-full', className)}>
            <Select
                value={value ?? undefined}
                onValueChange={(next) => onChange(next || null)}
            >
                <SelectTrigger
                    className="border-primary bg-background h-12 min-h-12 w-full rounded-none py-0 shadow-none data-[size=default]:h-12"
                    aria-label="Select nearest branch"
                >
                    <SelectValue placeholder={placeholder} />
                </SelectTrigger>
                <SelectContent>
                    {branches.map((branch) => (
                        <SelectItem key={branch.id} value={branch.id}>
                            {branch.name}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
        </div>
    );
}
