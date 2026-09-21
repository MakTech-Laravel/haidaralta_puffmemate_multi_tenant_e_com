import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import {
    formatDutyLine,
    type ShiftAssignmentRow,
    type TransferRow,
} from '@/components/admin/workforce-types';

export function TransferTimeline({ transfers }: { transfers: TransferRow[] }) {
    if (transfers.length === 0) {
        return (
            <p className="text-muted-foreground text-sm">
                No transfers recorded yet.
            </p>
        );
    }

    return (
        <div className="flex flex-col">
            {transfers.map((transfer, index) => (
                <div key={transfer.id}>
                    {index > 0 ? <Separator className="my-4" /> : null}
                    <div className="flex flex-wrap items-start justify-between gap-3">
                        <div className="min-w-0 space-y-1">
                            <p className="text-sm font-medium">
                                {transfer.from_branch_name ?? '—'} →{' '}
                                {transfer.to_branch_name ?? '—'}
                            </p>
                            <p className="text-muted-foreground text-sm">
                                {transfer.effective_date}
                                {transfer.from_role || transfer.to_role
                                    ? ` · ${transfer.from_role ?? '—'} → ${transfer.to_role ?? '—'}`
                                    : ''}
                            </p>
                            {transfer.reason ? (
                                <p className="text-muted-foreground text-sm">
                                    {transfer.reason}
                                </p>
                            ) : null}
                        </div>
                        <Badge variant="secondary">
                            {transfer.transfer_type_label}
                        </Badge>
                    </div>
                </div>
            ))}
        </div>
    );
}

export function ShiftAssignmentList({
    assignments,
}: {
    assignments: ShiftAssignmentRow[];
}) {
    if (assignments.length === 0) {
        return (
            <p className="text-muted-foreground text-sm">
                No duty windows assigned yet.
            </p>
        );
    }

    return (
        <div className="flex flex-col">
            {assignments.map((assignment, index) => (
                <div key={assignment.id}>
                    {index > 0 ? <Separator className="my-4" /> : null}
                    <div className="flex flex-wrap items-start justify-between gap-3">
                        <div className="min-w-0">
                            <p className="text-sm font-medium">
                                {assignment.branch_name
                                    ? `${assignment.branch_name} · `
                                    : ''}
                                {formatDutyLine(assignment)}
                            </p>
                            <p className="text-muted-foreground text-sm">
                                {assignment.shift_roster_period_name ??
                                    'Roster period'}
                                {assignment.covers_now ? ' · On duty now' : ''}
                            </p>
                        </div>
                        <Badge variant="secondary">
                            {assignment.status_label}
                        </Badge>
                    </div>
                </div>
            ))}
        </div>
    );
}
