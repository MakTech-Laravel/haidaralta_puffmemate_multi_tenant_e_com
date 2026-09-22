import type { ShiftTemplateOption } from '@/components/admin/workforce-types';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { formatAppClock } from '@/lib/datetime';

export function ShiftChangeDialog({
    open,
    templates,
    targetShiftTemplateId,
    addedCount,
    changedCount,
    removedCount,
    errors,
    processing,
    onOpenChange,
    onTargetChange,
    onConfirm,
}: {
    open: boolean;
    templates: ShiftTemplateOption[];
    targetShiftTemplateId: number | null;
    addedCount: number;
    changedCount: number;
    removedCount: number;
    errors: Record<string, string>;
    processing: boolean;
    onOpenChange: (open: boolean) => void;
    onTargetChange: (templateId: number | null) => void;
    onConfirm: () => void;
}) {
    const needsShift = addedCount > 0 || changedCount > 0;
    const hasAvailableShift = templates.length > 0;

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-xl">
                <DialogHeader>
                    <DialogTitle>
                        {needsShift
                            ? 'Confirm shift changes'
                            : 'Confirm removals'}
                    </DialogTitle>
                    <DialogDescription>
                        All staged changes are validated and saved together.
                    </DialogDescription>
                </DialogHeader>

                <div className="grid grid-cols-3 gap-2">
                    <ChangeCount label="Added" value={addedCount} />
                    <ChangeCount label="Changed" value={changedCount} />
                    <ChangeCount label="Removed" value={removedCount} />
                </div>

                {needsShift ? (
                    <div className="space-y-2">
                        <label
                            htmlFor="target-shift"
                            className="text-sm font-medium"
                        >
                            Shift
                        </label>
                        {hasAvailableShift ? (
                            <Select
                                value={
                                    targetShiftTemplateId
                                        ? String(targetShiftTemplateId)
                                        : undefined
                                }
                                onValueChange={(value) =>
                                    onTargetChange(Number(value))
                                }
                            >
                                <SelectTrigger
                                    id="target-shift"
                                    className="w-full"
                                >
                                    <SelectValue placeholder="Choose a shift" />
                                </SelectTrigger>
                                <SelectContent>
                                    {templates.map((template) => (
                                        <SelectItem
                                            key={template.id}
                                            value={String(template.id)}
                                        >
                                            {template.name} ·{' '}
                                            {formatAppClock(
                                                template.start_time,
                                            )}
                                            –{formatAppClock(template.end_time)}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        ) : (
                            <p className="border-border bg-muted/50 rounded-lg border p-3 text-sm">
                                No active shift is available for this user type
                                in this workspace. Create or activate a suitable
                                shift first.
                            </p>
                        )}
                        {errors.shift_template_id ? (
                            <p className="text-destructive text-sm">
                                {errors.shift_template_id}
                            </p>
                        ) : null}
                    </div>
                ) : null}

                {errors.schedule ? (
                    <p className="border-destructive/30 bg-destructive/10 text-destructive rounded-lg border p-3 text-sm">
                        {errors.schedule}
                    </p>
                ) : null}

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        disabled={processing}
                        onClick={() => onOpenChange(false)}
                    >
                        Cancel
                    </Button>
                    <Button
                        type="button"
                        disabled={
                            processing ||
                            (needsShift &&
                                (!hasAvailableShift ||
                                    targetShiftTemplateId === null))
                        }
                        onClick={onConfirm}
                    >
                        {processing ? <Spinner /> : null}
                        Save changes
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

function ChangeCount({ label, value }: { label: string; value: number }) {
    return (
        <div className="bg-muted/50 rounded-lg border px-3 py-2 text-center">
            <strong className="block text-lg">{value}</strong>
            <span className="text-muted-foreground text-xs">{label}</span>
        </div>
    );
}
