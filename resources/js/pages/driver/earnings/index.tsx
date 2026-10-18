import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowDownRight,
    ArrowUpRight,
    Banknote,
    CircleDollarSign,
    Minus,
    Sparkles,
    Wallet,
} from 'lucide-react';
import type { ReactNode } from 'react';
import { DriverEmptyState } from '@/components/driver/driver-empty-state';
import type {
    DriverEarnings,
    DriverEarningsPeriod,
} from '@/components/driver/driver-order-types';
import { Badge } from '@/components/ui/badge';
import {
    ToggleGroup,
    ToggleGroupItem,
} from '@/components/ui/toggle-group';
import { usePaymentGatewayLabel } from '@/lib/payment-gateway';
import { cn } from '@/lib/utils';
import { index as earningsIndex } from '@/routes/driver/earnings';
import { show as orderShow } from '@/routes/driver/orders';

type DriverEarningsIndexProps = {
    earnings: DriverEarnings;
    filters: {
        period: DriverEarningsPeriod;
    };
};

const periodOptions: Array<{ value: DriverEarningsPeriod; label: string }> = [
    { value: 'today', label: 'Today' },
    { value: '7d', label: '7 days' },
    { value: '30d', label: '30 days' },
];

function visitEarnings(period: DriverEarningsPeriod): void {
    router.get(
        earningsIndex.url(),
        period === 'today' ? {} : { period },
        {
            only: ['earnings', 'filters'],
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
}

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

function StatDelta({
    performance,
    mode,
    newLabel,
}: {
    performance: DriverEarnings['performance'];
    mode: 'money' | 'percent';
    newLabel: string;
}) {
    const Icon =
        performance.direction === 'up'
            ? ArrowUpRight
            : performance.direction === 'down'
              ? ArrowDownRight
              : performance.direction === 'new'
                ? Sparkles
                : Minus;

    const tone =
        performance.direction === 'up' || performance.direction === 'new'
            ? 'text-emerald-600'
            : performance.direction === 'down'
              ? 'text-rose-600'
              : 'text-muted-foreground';

    const changeText =
        performance.direction === 'new'
            ? newLabel
            : mode === 'money'
              ? performance.changeAmountLabel
              : performance.changePercent === null
                ? '0%'
                : `${performance.changePercent}%`;

    return (
        <p className={cn('flex items-center gap-0.5 text-xs font-medium', tone)}>
            <Icon className="size-3 shrink-0" aria-hidden />
            <span className="truncate">
                {changeText}{' '}
                <span className="text-muted-foreground font-normal">
                    {performance.comparisonLabel}
                </span>
            </span>
        </p>
    );
}

function StatDeltaSlot({ children }: { children?: ReactNode }) {
    return (
        <div className="mt-1 min-h-5">
            {children ?? <span className="invisible text-xs">—</span>}
        </div>
    );
}

export default function DriverEarningsIndex({
    earnings,
    filters,
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
                        {earnings.deliveries} deliveries · {earnings.periodLabel}
                    </p>
                </div>

                <ToggleGroup
                    type="single"
                    value={filters.period}
                    onValueChange={(value) => {
                        if (
                            value === 'today' ||
                            value === '7d' ||
                            value === '30d'
                        ) {
                            visitEarnings(value);
                        }
                    }}
                    variant="outline"
                    className="grid w-full grid-cols-3"
                    aria-label="Earnings period"
                >
                    {periodOptions.map((option) => (
                        <ToggleGroupItem
                            key={option.value}
                            value={option.value}
                            className="data-[state=on]:bg-foreground data-[state=on]:text-background h-11 w-full text-sm font-semibold"
                            data-test={`driver-earnings-period-${option.value}`}
                        >
                            {option.label}
                        </ToggleGroupItem>
                    ))}
                </ToggleGroup>

                <section className="bg-accent rounded-xl p-4">
                    <p className="text-muted-foreground text-sm font-medium">
                        {earnings.periodLabel}
                    </p>
                    <p className="text-foreground mt-1 text-3xl font-bold tracking-tight">
                        {earnings.totalLabel}
                    </p>
                </section>

                <div className="grid grid-cols-2 items-stretch gap-2.5 sm:grid-cols-4">
                    <div className="bg-accent flex flex-col space-y-1 rounded-xl p-3">
                        <p className="text-muted-foreground text-xs font-medium">
                            Previous period
                        </p>
                        <p className="text-foreground text-lg font-semibold tracking-tight">
                            {earnings.previousTotalLabel}
                        </p>
                        <StatDeltaSlot>
                            <StatDelta
                                performance={earnings.performance}
                                mode="money"
                                newLabel="New"
                            />
                        </StatDeltaSlot>
                    </div>
                    <div className="bg-accent flex flex-col space-y-1 rounded-xl p-3">
                        <p className="text-muted-foreground text-xs font-medium">
                            Avg / stop
                        </p>
                        <p className="text-foreground text-lg font-semibold tracking-tight">
                            {earnings.perStopLabel}
                        </p>
                        <StatDeltaSlot />
                    </div>
                    <div className="bg-accent flex flex-col space-y-1 rounded-xl p-3">
                        <p className="text-muted-foreground text-xs font-medium">
                            {codHeldLabel} held
                        </p>
                        <p className="text-foreground text-lg font-semibold tracking-tight">
                            {earnings.codHeldLabel}
                        </p>
                        <StatDeltaSlot />
                    </div>
                    <div className="bg-accent flex flex-col space-y-1 rounded-xl p-3">
                        <p className="text-muted-foreground text-xs font-medium">
                            Deliveries
                        </p>
                        <p className="text-foreground text-lg font-semibold tracking-tight">
                            {earnings.deliveries}
                        </p>
                        <StatDeltaSlot>
                            <StatDelta
                                performance={earnings.deliveriesPerformance}
                                mode="percent"
                                newLabel="New"
                            />
                        </StatDeltaSlot>
                    </div>
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
                                            <li key={`${day.label}-${entry.id}`}>
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
                        description="Completed drops from this period will show up here."
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
