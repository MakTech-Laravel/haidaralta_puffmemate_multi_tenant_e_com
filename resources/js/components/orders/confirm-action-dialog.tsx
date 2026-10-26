import { Loader2, type LucideIcon } from 'lucide-react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import {
    AlertDialog,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

type ConfirmActionVariant = 'default' | 'destructive';

interface ConfirmActionDialogProps {
    title: string;
    description: ReactNode;
    confirmLabel: string;
    icon: LucideIcon;
    variant?: ConfirmActionVariant;
    /** The trigger, rendered `asChild`. Omit when driving `open` yourself. */
    children?: ReactNode;
    onConfirm: () => void;
    processing?: boolean;
    open?: boolean;
    onOpenChange?: (open: boolean) => void;
}

/**
 * Accessible confirm card built on AlertDialog — same chrome as ConfirmDeleteDialog,
 * with optional icon badge and default/destructive confirm button.
 */
export function ConfirmActionDialog({
    title,
    description,
    confirmLabel,
    icon: Icon,
    variant = 'default',
    children,
    onConfirm,
    processing = false,
    open: controlledOpen,
    onOpenChange,
}: ConfirmActionDialogProps) {
    const [uncontrolledOpen, setUncontrolledOpen] = useState(false);

    const isControlled = controlledOpen !== undefined;
    const open = isControlled ? controlledOpen : uncontrolledOpen;
    const setOpen = (next: boolean) => {
        if (!isControlled) {
            setUncontrolledOpen(next);
        }

        onOpenChange?.(next);
    };

    const isDestructive = variant === 'destructive';

    return (
        <AlertDialog open={open} onOpenChange={setOpen}>
            {children ? (
                <AlertDialogTrigger asChild>{children}</AlertDialogTrigger>
            ) : null}
            <AlertDialogContent>
                <AlertDialogHeader>
                    <div className="flex flex-col items-center gap-3 sm:flex-row sm:items-start">
                        <span
                            className={cn(
                                'flex size-10 shrink-0 items-center justify-center rounded-full',
                                isDestructive
                                    ? 'bg-destructive/10 text-destructive'
                                    : 'bg-primary/10 text-primary',
                            )}
                        >
                            <Icon className="size-5" aria-hidden />
                        </span>
                        <div className="flex min-w-0 flex-1 flex-col gap-2 text-center sm:text-left">
                            <AlertDialogTitle>{title}</AlertDialogTitle>
                            <AlertDialogDescription>
                                {description}
                            </AlertDialogDescription>
                        </div>
                    </div>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel disabled={processing}>
                        Cancel
                    </AlertDialogCancel>
                    <Button
                        variant={isDestructive ? 'destructive' : 'default'}
                        disabled={processing}
                        onClick={(event) => {
                            event.preventDefault();
                            onConfirm();
                            setOpen(false);
                        }}
                    >
                        {processing ? (
                            <Loader2 className="h-4 w-4 animate-spin" />
                        ) : null}
                        {confirmLabel}
                    </Button>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}
