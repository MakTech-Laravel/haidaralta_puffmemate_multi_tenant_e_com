import { Loader2 } from 'lucide-react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import InputError from '@/components/input-error';
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
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

interface RejectReasonDialogProps {
    title?: string;
    description: ReactNode;
    confirmLabel?: string;
    reasonLabel?: string;
    reasonPlaceholder?: string;
    /** The trigger, rendered `asChild`. Omit when driving `open` yourself. */
    children?: ReactNode;
    onConfirm: (reason: string) => void;
    processing?: boolean;
    error?: string;
    /** Controlled visibility, for triggers that cannot stay mounted (e.g. a dropdown item). */
    open?: boolean;
    onOpenChange?: (open: boolean) => void;
}

/**
 * Rejection confirmation with a required reason, built on AlertDialog.
 *
 * The dialog stays open after confirming so a server validation error can be
 * shown; close it from the caller's success callback.
 */
export function RejectReasonDialog({
    title = 'Reject request',
    description,
    confirmLabel = 'Reject',
    reasonLabel = 'Reason',
    reasonPlaceholder,
    children,
    onConfirm,
    processing = false,
    error,
    open: controlledOpen,
    onOpenChange,
}: RejectReasonDialogProps) {
    const [uncontrolledOpen, setUncontrolledOpen] = useState(false);
    const [reason, setReason] = useState('');

    const isControlled = controlledOpen !== undefined;
    const open = isControlled ? controlledOpen : uncontrolledOpen;
    const setOpen = (next: boolean) => {
        if (!isControlled) {
            setUncontrolledOpen(next);
        }

        if (!next) {
            setReason('');
        }

        onOpenChange?.(next);
    };

    const trimmed = reason.trim();

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
                <div className="grid gap-2">
                    <Label htmlFor="reject-reason">{reasonLabel}</Label>
                    <Textarea
                        id="reject-reason"
                        rows={4}
                        value={reason}
                        placeholder={reasonPlaceholder}
                        disabled={processing}
                        onChange={(event) => setReason(event.target.value)}
                    />
                    <InputError message={error} />
                </div>
                <AlertDialogFooter>
                    <AlertDialogCancel disabled={processing}>
                        Cancel
                    </AlertDialogCancel>
                    <Button
                        variant="destructive"
                        disabled={processing || trimmed === ''}
                        onClick={(event) => {
                            event.preventDefault();
                            onConfirm(trimmed);
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
