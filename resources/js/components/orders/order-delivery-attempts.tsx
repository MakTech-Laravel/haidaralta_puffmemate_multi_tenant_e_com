import { CircleAlert } from 'lucide-react';
import { SectionCard } from '@/components/orders/order-detail-section';
import { formatAppDateTime } from '@/lib/datetime';

export type OrderDeliveryAttempt = {
    id: number;
    reason: string;
    reasonLabel: string;
    note: string | null;
    attemptedAt: string | null;
    driverName: string | null;
};

export function deliveryAttemptCountLabel(count: number): string {
    return count === 1 ? '1 attempt' : `${count} attempts`;
}

/** Muted count shown beside return actions while canReturn / canConfirmReturn. */
export function OrderDeliveryAttemptCount({
    attempts,
}: {
    attempts: OrderDeliveryAttempt[];
}) {
    if (attempts.length === 0) {
        return null;
    }

    return (
        <span className="text-muted-foreground self-center text-sm">
            {deliveryAttemptCountLabel(attempts.length)}
        </span>
    );
}

export function DeliveryAttemptRow({
    attempt,
    index,
}: {
    attempt: OrderDeliveryAttempt;
    index?: number;
}) {
    return (
        <div className="space-y-1">
            <p className="text-sm font-medium">
                {index !== undefined ? (
                    <>
                        Attempt {index + 1}
                        <span className="text-muted-foreground font-normal">
                            {' '}
                            · {attempt.reasonLabel}
                        </span>
                    </>
                ) : (
                    attempt.reasonLabel
                )}
            </p>
            {attempt.note ? (
                <p className="text-muted-foreground text-sm">
                    Note: {attempt.note}
                </p>
            ) : null}
            <p className="text-muted-foreground text-xs">
                {[
                    attempt.driverName,
                    attempt.attemptedAt
                        ? formatAppDateTime(attempt.attemptedAt)
                        : null,
                ]
                    .filter(Boolean)
                    .join(' · ') || '—'}
            </p>
        </div>
    );
}

/**
 * Latest can't-deliver reason while staff still need to Begin/Start return.
 */
export function OrderDeliveryAttemptPending({
    attempts,
}: {
    attempts: OrderDeliveryAttempt[];
}) {
    const latest = attempts.at(-1);

    if (!latest) {
        return null;
    }

    return (
        <div className="bg-muted/40 rounded-lg p-3">
            <DeliveryAttemptRow attempt={latest} />
        </div>
    );
}

type OrderDeliveryAttemptsProps = {
    attempts: OrderDeliveryAttempt[];
    /** When false, hide even if attempts exist (e.g. still awaiting Begin return). */
    visible?: boolean;
};

export function OrderDeliveryAttempts({
    attempts,
    visible = true,
}: OrderDeliveryAttemptsProps) {
    if (!visible || attempts.length === 0) {
        return null;
    }

    return (
        <SectionCard icon={CircleAlert} title="Delivery attempts">
            <ul className="divide-border/60 divide-y">
                {attempts.map((attempt, index) => (
                    <li
                        key={attempt.id}
                        className="py-3 first:pt-0 last:pb-0"
                    >
                        <DeliveryAttemptRow attempt={attempt} index={index} />
                    </li>
                ))}
            </ul>
        </SectionCard>
    );
}
