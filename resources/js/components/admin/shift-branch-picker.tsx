import { InfiniteScrollCombobox } from '@/components/stock/infinite-scroll-combobox';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

export function ShiftBranchPicker({
    optionsUrl,
    value,
    selectedLabel,
    onChange,
    className,
    size = 'default',
}: {
    optionsUrl: string;
    value: string;
    selectedLabel?: string | null;
    onChange: (branchId: string) => void;
    className?: string;
    size?: 'default' | 'lg';
}) {
    return (
        <div className={cn('flex w-full max-w-sm flex-col gap-1.5', className)}>
            <Label>Branch</Label>
            <InfiniteScrollCombobox
                optionsUrl={optionsUrl}
                value={value || null}
                onValueChange={(next) => {
                    if (next) {
                        onChange(next);
                    }
                }}
                selectedLabel={selectedLabel}
                placeholder="Choose a branch"
                searchPlaceholder="Search branches…"
                emptyText="No branches found."
                descriptionKey={null}
                className={size === 'lg' ? 'h-11 text-base' : undefined}
            />
        </div>
    );
}
