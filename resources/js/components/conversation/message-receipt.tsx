import { AlertCircle, Check, CheckCheck, Clock3 } from 'lucide-react';

import { cn } from '@/lib/utils';

import type { ParticipantReceipt, ReceiptState } from './types';

/**
 * Resolve a message's tick from the participant markers.
 *
 * Delivered / read when *any* other participant has the marker (shared staff
 * threads never reach "everyone read"). The sender's own markers are ignored.
 */
export function receiptStateFor(
    messageId: number,
    viewerId: number,
    receipts: ParticipantReceipt[],
): ReceiptState {
    const others = receipts.filter((receipt) => receipt.user_id !== viewerId);

    if (others.length === 0) {
        return 'sent';
    }

    const anyRead = others.some(
        (receipt) => receipt.last_read_message_id >= messageId,
    );

    if (anyRead) {
        return 'read';
    }

    const anyDelivered = others.some(
        (receipt) => receipt.last_delivered_message_id >= messageId,
    );

    return anyDelivered ? 'delivered' : 'sent';
}

const LABELS: Record<ReceiptState, string> = {
    sending: 'Sending',
    failed: 'Failed to send',
    sent: 'Sent',
    delivered: 'Delivered',
    read: 'Read',
};

export function MessageReceipt({ state }: { state: ReceiptState }) {
    const Icon = {
        sending: Clock3,
        failed: AlertCircle,
        sent: Check,
        delivered: CheckCheck,
        read: CheckCheck,
    }[state];

    return (
        <Icon
            role="img"
            aria-label={LABELS[state]}
            className={cn(
                'size-3.5 shrink-0',
                state === 'failed' && 'text-destructive',
                state === 'read' && 'text-emerald-600',
                (state === 'sending' ||
                    state === 'sent' ||
                    state === 'delivered') &&
                    'text-muted-foreground',
            )}
        />
    );
}
