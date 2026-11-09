import { Head, Link, router } from '@inertiajs/react';
import type { ReactNode } from 'react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatAppDateTime } from '@/lib/datetime';
import { formatClock, formatWindow } from '@/lib/shift-format';
import { cn } from '@/lib/utils';

type DeliveryShiftDetail = {
    id: number;
    name: string;
    start_time: string;
    end_time: string;
    is_active: boolean;
    created_by_name: string | null;
    created_at: string | null;
    updated_at: string | null;
};

function Fact({ label, value }: { label: string; value: ReactNode }) {
    return (
        <div className="flex min-w-0 flex-col gap-1">
            <dt className="text-muted-foreground text-[11px] font-medium tracking-wide uppercase">
                {label}
            </dt>
            <dd className="text-sm font-medium wrap-break-word">{value}</dd>
        </div>
    );
}

export default function PlatformDeliveryShiftShow({
    shift,
}: {
    shift: DeliveryShiftDetail;
}) {
    return (
        <>
            <Head title={shift.name} />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="space-y-2">
                        <Heading
                            title={shift.name}
                            description="Delivery shift details"
                        />
                        <div className="flex flex-wrap gap-2">
                            <Badge
                                variant={
                                    shift.is_active ? 'default' : 'outline'
                                }
                            >
                                {shift.is_active ? 'Active' : 'Off'}
                            </Badge>
                        </div>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button variant="outline" asChild>
                            <Link href="/platform/delivery-shifts">Back</Link>
                        </Button>
                        <Button variant="outline" asChild>
                            <Link
                                href={`/platform/delivery-shifts/${shift.id}/edit`}
                            >
                                Edit
                            </Link>
                        </Button>
                        <Button
                            type="button"
                            variant="destructive"
                            onClick={() => {
                                if (confirm(`Remove “${shift.name}”?`)) {
                                    router.delete(
                                        `/platform/delivery-shifts/${shift.id}`,
                                    );
                                }
                            }}
                        >
                            Delete
                        </Button>
                    </div>
                </div>

                <section
                    className={cn(
                        'border-border/80 bg-card rounded-2xl border p-5 shadow-sm',
                        'sm:p-6',
                    )}
                >
                    <dl className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        <Fact label="Name" value={shift.name} />
                        <Fact
                            label="Window"
                            value={
                                formatWindow(
                                    shift.start_time,
                                    shift.end_time,
                                ) ||
                                `${formatClock(shift.start_time)} – ${formatClock(shift.end_time)}`
                            }
                        />
                        <Fact
                            label="Start"
                            value={formatClock(shift.start_time)}
                        />
                        <Fact
                            label="End"
                            value={formatClock(shift.end_time)}
                        />
                        <Fact
                            label="Created by"
                            value={shift.created_by_name ?? '—'}
                        />
                        <Fact
                            label="Created"
                            value={formatAppDateTime(shift.created_at) || '—'}
                        />
                        <Fact
                            label="Updated"
                            value={formatAppDateTime(shift.updated_at) || '—'}
                        />
                    </dl>
                </section>
            </div>
        </>
    );
}

PlatformDeliveryShiftShow.layout = {
    breadcrumbs: [
        { title: 'Delivery shifts', href: '/platform/delivery-shifts' },
        {
            title: 'Details',
            href: '/platform/delivery-shifts',
        },
    ],
};
