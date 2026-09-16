import { useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

type CancelOrderDialogProps = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    orderNumber?: string;
};

export function CancelOrderDialog({
    open,
    onOpenChange,
    orderNumber,
}: CancelOrderDialogProps) {
    const [reason, setReason] = useState('');

    useEffect(() => {
        if (!open) {
            setReason('');
        }
    }, [open]);

    const submit = () => {
        onOpenChange(false);
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle className="text-foreground text-left text-lg font-bold">
                        Cancel Reason
                        {orderNumber ? (
                            <span className="sr-only"> for {orderNumber}</span>
                        ) : null}
                    </DialogTitle>
                </DialogHeader>

                <div className="flex flex-col gap-2">
                    <Label
                        htmlFor="cancel-order-reason"
                        className="text-foreground font-normal"
                    >
                        Write cancel reason
                    </Label>
                    <Textarea
                        id="cancel-order-reason"
                        value={reason}
                        onChange={(event) => setReason(event.target.value)}
                        className="min-h-28 resize-y"
                    />
                </div>

                <Button
                    type="button"
                    size="lg"
                    className="mt-2 h-11 w-full rounded-md text-sm font-semibold"
                    onClick={submit}
                >
                    Submit
                </Button>
            </DialogContent>
        </Dialog>
    );
}
