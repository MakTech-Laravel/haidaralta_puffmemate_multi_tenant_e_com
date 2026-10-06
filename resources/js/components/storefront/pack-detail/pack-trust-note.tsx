import { CircleCheck } from 'lucide-react';
import { cn } from '@/lib/utils';

type PackTrustNoteProps = {
    className?: string;
};

export function PackTrustNote({ className }: PackTrustNoteProps) {
    return (
        <div
            className={cn(
                'border-border bg-card flex items-start gap-3 rounded-xl border p-4',
                className,
            )}
        >
            <CircleCheck className="text-primary mt-0.5 size-5 shrink-0" />
            <div className="min-w-0">
                <p className="text-foreground text-sm font-bold">
                    100% Genuine Australian Duty-Paid Licensed Stock
                </p>
                <p className="text-muted-foreground mt-1 text-xs leading-relaxed">
                    Direct distribution from verified licensed bonded
                    warehouses. Fully compliant with Australian Standard Plain
                    Packaging regulations and federal excise compliance seals.
                </p>
            </div>
        </div>
    );
}
