import InputError from '@/components/input-error';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';

export const ADJUSTMENT_REASON_NOTE_MAX = 255;

export function AdjustmentReasonNote({
    id,
    value,
    onChange,
    error,
}: {
    id: string;
    value: string;
    onChange: (value: string) => void;
    error?: string;
}) {
    const count = value.length;
    const atLimit = count >= ADJUSTMENT_REASON_NOTE_MAX;

    return (
        <div className="grid gap-2">
            <div className="flex items-center justify-between gap-3">
                <Label htmlFor={id}>Describe the reason</Label>
                <p
                    id={`${id}-count`}
                    className={cn(
                        'text-xs tabular-nums',
                        atLimit ? 'text-destructive' : 'text-muted-foreground',
                    )}
                >
                    {count}/{ADJUSTMENT_REASON_NOTE_MAX}
                </p>
            </div>
            <Textarea
                id={id}
                value={value}
                rows={4}
                maxLength={ADJUSTMENT_REASON_NOTE_MAX}
                placeholder="e.g. Returned from display"
                aria-describedby={`${id}-count`}
                className="min-h-28 resize-y"
                onChange={(event) =>
                    onChange(
                        event.target.value.slice(0, ADJUSTMENT_REASON_NOTE_MAX),
                    )
                }
            />
            <InputError message={error} />
        </div>
    );
}
