import { Head, Link, router } from '@inertiajs/react';
import { Eye, Pencil, Plus, Trash2 } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Switch } from '@/components/ui/switch';
import { formatClock, formatWindow } from '@/lib/shift-format';

type DeliveryShiftRow = {
    id: number;
    name: string;
    start_time: string;
    end_time: string;
    is_active: boolean;
};

export default function PlatformDeliveryShiftsIndex({
    shifts,
}: {
    shifts: DeliveryShiftRow[];
}) {
    return (
        <>
            <Head title="Delivery shifts" />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <Heading
                        title="Delivery shifts"
                        description="Named same-day delivery windows for the platform."
                    />
                    <Button asChild>
                        <Link href="/platform/delivery-shifts/create">
                            <Plus className="size-4" />
                            Add shift
                        </Link>
                    </Button>
                </div>

                {shifts.length === 0 ? (
                    <div className="border-border/80 bg-card rounded-2xl border p-8 text-center shadow-sm">
                        <p className="text-muted-foreground text-sm">
                            No delivery shifts yet.
                        </p>
                    </div>
                ) : (
                    <div className="space-y-3">
                        {shifts.map((shift) => (
                            <div
                                key={shift.id}
                                className="border-border/80 bg-card flex flex-col gap-4 rounded-2xl border p-4 shadow-sm sm:flex-row sm:items-center"
                            >
                                <div className="min-w-0 flex-1">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <p className="text-foreground font-semibold">
                                            {shift.name}
                                        </p>
                                        {!shift.is_active ? (
                                            <Badge variant="outline">Off</Badge>
                                        ) : null}
                                    </div>
                                    <p className="text-muted-foreground mt-1 text-sm">
                                        {formatWindow(
                                            shift.start_time,
                                            shift.end_time,
                                        ) ||
                                            `${formatClock(shift.start_time)} – ${formatClock(shift.end_time)}`}
                                    </p>
                                </div>
                                <div className="flex flex-wrap items-center gap-2 sm:justify-end">
                                    <Switch
                                        checked={shift.is_active}
                                        onCheckedChange={(checked) =>
                                            router.put(
                                                `/platform/delivery-shifts/${shift.id}`,
                                                {
                                                    name: shift.name,
                                                    start_time: shift.start_time,
                                                    end_time: shift.end_time,
                                                    is_active: checked,
                                                },
                                                { preserveScroll: true },
                                            )
                                        }
                                        aria-label={`Toggle ${shift.name}`}
                                    />
                                    <Button
                                        variant="outline"
                                        size="icon"
                                        asChild
                                    >
                                        <Link
                                            href={`/platform/delivery-shifts/${shift.id}`}
                                        >
                                            <Eye className="size-4" />
                                        </Link>
                                    </Button>
                                    <Button
                                        variant="outline"
                                        size="icon"
                                        asChild
                                    >
                                        <Link
                                            href={`/platform/delivery-shifts/${shift.id}/edit`}
                                        >
                                            <Pencil className="size-4" />
                                        </Link>
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="icon"
                                        onClick={() => {
                                            if (
                                                confirm(
                                                    `Remove “${shift.name}”?`,
                                                )
                                            ) {
                                                router.delete(
                                                    `/platform/delivery-shifts/${shift.id}`,
                                                    { preserveScroll: true },
                                                );
                                            }
                                        }}
                                    >
                                        <Trash2 className="size-4" />
                                    </Button>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}

PlatformDeliveryShiftsIndex.layout = {
    breadcrumbs: [
        { title: 'Delivery shifts', href: '/platform/delivery-shifts' },
    ],
};
