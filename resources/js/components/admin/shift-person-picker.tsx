import { InfiniteScrollCombobox } from '@/components/stock/infinite-scroll-combobox';
import { Label } from '@/components/ui/label';

export function ShiftPersonPicker({
    optionsUrl,
    value,
    selectedLabel,
    disabled,
    onChange,
}: {
    optionsUrl: string | null;
    value: number | null;
    selectedLabel?: string | null;
    disabled: boolean;
    onChange: (userId: number | null) => void;
}) {
    return (
        <div className="space-y-2">
            <Label htmlFor="shift-person">User</Label>
            <InfiniteScrollCombobox
                id="shift-person"
                optionsUrl={optionsUrl ?? ''}
                value={value ? String(value) : null}
                selectedLabel={selectedLabel}
                disabled={disabled || optionsUrl === null}
                onValueChange={(next) =>
                    onChange(next === null ? null : Number(next))
                }
                placeholder="Choose a user"
                searchPlaceholder="Search users…"
                emptyText="No eligible users found."
                loadErrorText="Could not load users."
                descriptionKey={null}
                className="h-11"
            />
        </div>
    );
}
