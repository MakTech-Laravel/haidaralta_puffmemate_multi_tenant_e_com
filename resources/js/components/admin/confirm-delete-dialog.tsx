import { Loader2 } from 'lucide-react';
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

interface ConfirmDeleteDialogProps {
    title?: string;
    description: ReactNode;
    confirmLabel?: string;
    /** The trigger, rendered `asChild`. Omit when driving `open` yourself. */
    children?: ReactNode;
    onConfirm: () => void;
    processing?: boolean;
    /** Controlled visibility, for triggers that cannot stay mounted (e.g. a dropdown item). */
    open?: boolean;
    onOpenChange?: (open: boolean) => void;
}

/**
 * Accessible delete confirmation built on AlertDialog.
 *
 * Uncontrolled: wrap the trigger element as `children`. Controlled: pass `open`
 * and `onOpenChange` and omit `children`, which is required when the trigger
 * lives inside a menu that unmounts on select.
 */
export function ConfirmDeleteDialog({
    title = 'Are you absolutely sure?',
    description,
    confirmLabel = 'Delete',
    children,
    onConfirm,
    processing = false,
    open: controlledOpen,
    onOpenChange,
}: ConfirmDeleteDialogProps) {
    const [uncontrolledOpen, setUncontrolledOpen] = useState(false);

    const isControlled = controlledOpen !== undefined;
    const open = isControlled ? controlledOpen : uncontrolledOpen;
    const setOpen = (next: boolean) => {
        if (!isControlled) {
            setUncontrolledOpen(next);
        }

        onOpenChange?.(next);
    };

    return (
        <AlertDialog open={open} onOpenChange={setOpen}>
            {children && (
                <AlertDialogTrigger asChild>{children}</AlertDialogTrigger>
            )}
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>{title}</AlertDialogTitle>
                    <AlertDialogDescription>
                        {description}
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel disabled={processing}>
                        Cancel
                    </AlertDialogCancel>
                    <Button
                        variant="destructive"
                        disabled={processing}
                        onClick={(event) => {
                            event.preventDefault();
                            onConfirm();
                            setOpen(false);
                        }}
                    >
                        {processing && (
                            <Loader2 className="h-4 w-4 animate-spin" />
                        )}
                        {confirmLabel}
                    </Button>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}
