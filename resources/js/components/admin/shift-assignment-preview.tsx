import { ShiftPeriodSpan } from '@/components/admin/shift-period-span';
import { ShiftTimeBar } from '@/components/admin/shift-time-bar';
import type {
    AssignablePerson,
    BranchOption,
    ShiftPeriodOption,
    ShiftTemplateOption,
} from '@/components/admin/workforce-types';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import {
    formatDayRange,
    formatWindow,
    initials,
} from '@/lib/shift-format';

export function ShiftAssignmentPreview({
    person,
    template,
    period,
    branch,
}: {
    person?: AssignablePerson;
    template?: ShiftTemplateOption;
    period?: ShiftPeriodOption;
    branch?: BranchOption;
}) {
    const ready = Boolean(person || template || period);

    return (
        <aside className="bg-card lg:sticky lg:top-6 space-y-4 rounded-2xl border p-5">
            <p className="text-muted-foreground text-[11px] font-medium tracking-[0.16em] uppercase">
                Assignment
            </p>
            {ready ? (
                <div className="space-y-4">
                    {person ? (
                        <div className="flex items-center gap-3">
                            <Avatar className="size-10">
                                {person.avatar_url ? (
                                    <AvatarImage
                                        src={person.avatar_url}
                                        alt={person.name}
                                    />
                                ) : null}
                                <AvatarFallback>
                                    {initials(person.name)}
                                </AvatarFallback>
                            </Avatar>
                            <div className="min-w-0">
                                <p className="truncate text-sm font-medium">
                                    {person.name}
                                </p>
                                <p className="text-muted-foreground text-xs">
                                    {person.user_type_label ?? person.user_type}
                                    {branch ? ` · ${branch.name}` : ''}
                                </p>
                            </div>
                        </div>
                    ) : null}
                    {template ? (
                        <div className="space-y-2">
                            <p className="text-sm font-medium">
                                {template.name}
                            </p>
                            <p className="text-muted-foreground text-xs">
                                {formatWindow(
                                    template.start_time,
                                    template.end_time,
                                    template.is_overnight,
                                )}
                            </p>
                            <ShiftTimeBar
                                start={template.start_time}
                                end={template.end_time}
                                overnight={template.is_overnight}
                            />
                        </div>
                    ) : null}
                    {period ? (
                        <div className="space-y-2">
                            <p className="text-sm font-medium">{period.name}</p>
                            <p className="text-muted-foreground text-xs">
                                {formatDayRange(
                                    period.start_date,
                                    period.end_date,
                                )}
                            </p>
                            <ShiftPeriodSpan
                                startDate={period.start_date}
                                endDate={period.end_date}
                                containsToday={period.contains_today}
                                daysElapsed={period.days_elapsed}
                                daysTotal={period.days_total}
                            />
                        </div>
                    ) : null}
                </div>
            ) : (
                <p className="text-muted-foreground text-sm">
                    Choose a person, window, and period to preview the
                    assignment.
                </p>
            )}
        </aside>
    );
}
