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
                    className="bg-card h-11 min-h-11 w-full rounded-full py-0 data-[size=default]:h-11"
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
