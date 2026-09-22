import {
    CalendarDays,
    ChevronLeft,
    ChevronRight,
    LockKeyhole,
} from 'lucide-react';
import type { ShiftAssignmentGroupsPayload } from '@/components/admin/workforce-types';
import { AppDateRange } from '@/components/app-datetime';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatAppClock } from '@/lib/datetime';

export function AssignedShiftGroupList({
    groups,
    onPageChange,
}: {
    groups: ShiftAssignmentGroupsPayload;
    onPageChange: (page: number) => void;
}) {
    return (
        <section className="border-border/80 bg-card rounded-2xl border p-4 shadow-sm sm:p-6">
            <div className="mb-4 flex items-end justify-between gap-3">
                <div>
                    <h2 className="text-lg font-semibold">Assigned shifts</h2>
                    <p className="text-muted-foreground text-sm">
                        Consecutive dates are grouped by shift.
                    </p>
                </div>
                <Badge variant="outline">{groups.meta.total} groups</Badge>
            </div>

            {groups.data.length === 0 ? (
                <div className="text-muted-foreground flex flex-col items-center gap-2 rounded-xl border border-dashed py-10 text-center">
                    <CalendarDays className="size-6" />
                    <p className="text-sm">No assigned shifts yet.</p>
                </div>
            ) : (
                <div className="space-y-3">
                    {groups.data.map((group) => (
                        <article
                            key={group.key}
                            className="flex flex-col gap-3 rounded-xl border p-4 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <div className="min-w-0">
                                <div className="flex flex-wrap items-center gap-2">
                                    <h3 className="font-semibold">
                                        {group.name}
                                    </h3>
                                    {group.is_past ? (
                                        <Badge variant="secondary">Past</Badge>
                                    ) : null}
                                    {!group.editable ? (
                                        <Badge variant="outline">
                                            <LockKeyhole /> Read-only
                                        </Badge>
                                    ) : null}
                                </div>
                                <p className="text-muted-foreground mt-1 text-sm">
                                    {formatAppClock(group.start_time)}–
                                    {formatAppClock(group.end_time)}
                                </p>
                            </div>
                            <div className="text-sm sm:text-right">
                                <p className="font-medium">
                                    <AppDateRange
                                        start={group.start_date}
                                        end={group.end_date}
                                    />
                                </p>
                                <p className="text-muted-foreground">
                                    {group.date_count}{' '}
                                    {group.date_count === 1 ? 'date' : 'dates'}
                                </p>
                            </div>
                        </article>
                    ))}
                </div>
            )}

            {groups.meta.last_page > 1 ? (
                <div className="mt-5 flex items-center justify-between border-t pt-4">
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        disabled={groups.meta.current_page <= 1}
                        onClick={() =>
                            onPageChange(groups.meta.current_page - 1)
                        }
                    >
                        <ChevronLeft /> Previous
                    </Button>
                    <span className="text-muted-foreground text-sm">
                        Page {groups.meta.current_page} of{' '}
                        {groups.meta.last_page}
                    </span>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        disabled={
                            groups.meta.current_page >= groups.meta.last_page
                        }
                        onClick={() =>
                            onPageChange(groups.meta.current_page + 1)
                        }
                    >
                        Next <ChevronRight />
                    </Button>
                </div>
            ) : null}
        </section>
    );
}
