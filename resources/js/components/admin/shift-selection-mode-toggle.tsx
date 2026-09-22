import type { ShiftSelectionMode } from '@/components/admin/shift-assignment-selection';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';

export function ShiftSelectionModeToggle({
    mode,
    onChange,
}: {
    mode: ShiftSelectionMode;
    onChange: (mode: ShiftSelectionMode) => void;
}) {
    return (
        <div className="bg-muted/50 flex items-center gap-3 rounded-lg border px-3 py-2">
            <Switch
                id="shift-range-mode"
                checked={mode === 'range'}
                onCheckedChange={(checked) =>
                    onChange(checked ? 'range' : 'manual')
                }
            />
            <Label htmlFor="shift-range-mode" className="cursor-pointer">
                {mode === 'range' ? 'Group selection' : 'One-by-one selection'}
            </Label>
        </div>
    );
}
