import { Head, Link } from '@inertiajs/react';
import { Banknote, CircleDollarSign, Wallet } from 'lucide-react';
import { DriverEmptyState } from '@/components/driver/driver-empty-state';
import type { DriverEarnings } from '@/components/driver/driver-order-types';
import { Badge } from '@/components/ui/badge';
import { usePaymentGatewayLabel } from '@/lib/payment-gateway';
import { index as earningsIndex } from '@/routes/driver/earnings';
import { show as orderShow } from '@/routes/driver/orders';

type DriverEarningsIndexProps = {
    earnings: DriverEarnings;
};

function EarningsEntryBadge({ paymentMethod }: { paymentMethod: string }) {
    const label = usePaymentGatewayLabel(paymentMethod, 'COD');

    if (paymentMethod !== 'cod') {
        return null;
    }

    return (
        <Badge className="bg-muted-foreground/15 text-foreground shrink-0 rounded-sm border-transparent px-1.5 py-0 text-[11px] font-medium">
            {label}
        </Badge>
    );
}

export default function DriverEarningsIndex({
    earnings,
}: DriverEarningsIndexProps) {
    const hasEntries = earnings.days.some((day) => day.entries.length > 0);
    const codHeldLabel = usePaymentGatewayLabel('cod', 'COD');

    return (
        <>
            <Head title="Earnings" />

            <div className="flex flex-col gap-5">
                <div className="space-y-1">
                    <h1 className="text-foreground text-xl font-bold tracking-tight sm:text-2xl">
                        Earnings
                    </h1>
                    <p className="text-muted-foreground text-sm">
                        {earnings.dropsToday} completed today ·{' '}
                        {earnings.dropsWeek} this week
                    </p>
                </div>

                <section className="bg-accent rounded-xl p-4">
                    <p className="text-muted-foreground text-sm font-medium">
                        Today
                    </p>
                    <p className="text-foreground mt-1 text-3xl font-bold tracking-tight">
                        {earnings.todayLabel}
                    </p>
                    <p className="text-muted-foreground mt-1 text-sm">
                        Payout {earnings.payoutStatus.toLowerCase()}
                    </p>
                </section>

                <div className="grid grid-cols-2 gap-2.5 sm:grid-cols-4">
                    {[
                        {
                            label: 'This week',
                            value: earnings.weekLabel,
                        },
                        {
                            label: 'Tips',
                            value: earnings.tipsLabel,
                        },
                        {
                            label: 'Avg / stop',
                            value: earnings.perStopLabel,
                        },
                        {
                            label: `${codHeldLabel} held`,
                            value: earnings.codHeldLabel,
                        },
                    ].map((stat) => (
                        <div
                            key={stat.label}
                            className="bg-accent space-y-1 rounded-xl p-3"
                        >
                            <p className="text-muted-foreground text-xs font-medium">
                                {stat.label}
                            </p>
                            <p className="text-foreground text-lg font-semibold tracking-tight">
                                {stat.value}
                            </p>
                        </div>
                    ))}
                </div>

                {hasEntries ? (
                    <div className="flex flex-col gap-6">
                        {earnings.days.map((day) =>
                            day.entries.length === 0 ? null : (
                                <section
                                    key={day.label}
                                    className="flex flex-col gap-2.5"
                                >
                                    <div className="flex items-end justify-between gap-2">
                                        <h2 className="text-muted-foreground text-sm font-medium tracking-wide uppercase">
                                            {day.label}
                                        </h2>
                                        <p className="text-foreground text-sm font-semibold">
                                            {day.totalLabel}
                                        </p>
                                    </div>
                                    <ul className="flex flex-col gap-2.5">
                                        {day.entries.map((entry) => (
                                            <li key={entry.id}>
                                                <Link
                                                    href={orderShow.url(
                                                        entry.id,
                                                    )}
                                                    prefetch
                                                    className="bg-accent flex items-start gap-3 rounded-xl p-3 transition-opacity hover:opacity-95 sm:p-4"
                                                >
                                                    <span className="bg-background flex size-9 shrink-0 items-center justify-center rounded-full">
                                                        {entry.paymentMethod ===
                                                        'cod' ? (
                                                            <Banknote className="size-4" />
                                                        ) : (
                                                            <CircleDollarSign className="size-4" />
                                                        )}
                                                    </span>
                                                    <div className="min-w-0 flex-1 space-y-0.5">
                                                        <div className="flex items-center gap-2">
                                                            <p className="text-foreground min-w-0 truncate text-sm font-semibold">
                                                                {
                                                                    entry.customerName
                                                                }
                                                            </p>
                                                            <EarningsEntryBadge
                                                                paymentMethod={
                                                                    entry.paymentMethod
                                                                }
                                                            />
                                                        </div>
                                                        <p className="text-muted-foreground truncate text-sm">
                                                            {entry.areaLabel} ·{' '}
                                                            {entry.orderNumber}
                                                        </p>
                                                    </div>
                                                    <div className="shrink-0 text-right">
                                                        <p className="text-foreground text-sm font-semibold">
                                                            {entry.amountLabel}
                                                        </p>
                                                        <p className="text-muted-foreground text-xs">
                                                            {entry.tipLabel} tip
                                                        </p>
                                                    </div>
                                                </Link>
                                            </li>
                                        ))}
                                    </ul>
                                </section>
                            ),
                        )}
                    </div>
                ) : (
                    <DriverEmptyState
                        icon={Wallet}
                        title="No earnings yet"
                        description="Completed drops from this shift will show up here."
                    />
                )}
            </div>
        </>
    );
}

DriverEarningsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Earnings',
            href: earningsIndex.url(),
        },
    ],
};
