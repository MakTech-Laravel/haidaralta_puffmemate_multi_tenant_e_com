import { InfiniteScrollCombobox } from '@/components/stock/infinite-scroll-combobox';
import { Label } from '@/components/ui/label';

export function ShiftBranchPicker({
    optionsUrl,
    value,
    selectedLabel,
    onChange,
}: {
    optionsUrl: string;
    value: string;
    selectedLabel?: string | null;
    onChange: (branchId: string) => void;
}) {
    return (
        <div className="flex w-full max-w-sm flex-col gap-1.5">
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
            />
        </div>
    );
}
