import { Form, Head, Link, setLayoutProps } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import ShiftAssignmentController from '@/actions/App/Http/Controllers/Branch/ShiftAssignmentController';
import ShiftTemplateController from '@/actions/App/Http/Controllers/Branch/ShiftTemplateController';
import { ShiftAssignmentCalendar } from '@/components/admin/shift-assignment-calendar';
import {
    emptyShiftAssignmentFields,
    ShiftAssignmentFields,
} from '@/components/admin/shift-assignment-fields';
import { exceptionsFromSavedDays } from '@/components/admin/shift-duty-calendar';
import { ShiftPageHeader } from '@/components/admin/shift-page-header';
import type {
    AssignablePerson,
    ShiftAssignmentRow,
    ShiftDayException,
    ShiftDutyDay,
    ShiftTemplateOption,
} from '@/components/admin/workforce-types';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';

type Branch = { id: number; name: string; slug: string };

export default function BranchShiftsAssign({
    branch,
    people,
    templates,
    assignment = null,
    days = [],
    otherDays = [],
    scheduledDays = [],
}: {
    branch: Branch;
    people: AssignablePerson[];
    templates: ShiftTemplateOption[];
    assignment?: ShiftAssignmentRow | null;
    days?: ShiftDutyDay[];
    otherDays?: ShiftDutyDay[];
    scheduledDays?: ShiftDutyDay[];
}) {
    setLayoutProps({
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: `/branch/${branch.slug}/dashboard`,
            },
            {
                title: 'Shifts',
                href: ShiftTemplateController.index.url(branch.slug),
            },
            {
                title: assignment ? 'Edit assignment' : 'Assign',
                href: assignment
                    ? ShiftAssignmentController.edit.url({
                          branch: branch.slug,
                          user_shift_assignment: assignment.id,
                      })
                    : ShiftAssignmentController.create.url(branch.slug),
            },
        ],
    });

    const [fields, setFields] = useState(() => ({
        ...emptyShiftAssignmentFields(),
        user_id: assignment ? String(assignment.user_id) : '',
        branch_id: String(branch.id),
        shift_template_id: assignment
            ? String(assignment.shift_template_id)
            : '',
        start_date: assignment?.start_date ?? '',
        end_date: assignment?.end_date ?? '',
        days_of_week: assignment?.days_of_week?.length
            ? assignment.days_of_week
            : emptyShiftAssignmentFields().days_of_week,
        reason: '',
    }));
    const [exceptions, setExceptions] = useState<ShiftDayException[]>(() =>
        exceptionsFromSavedDays(
            days,
            assignment?.days_of_week ?? emptyShiftAssignmentFields().days_of_week,
            assignment?.shift_template_id ?? null,
        ),
    );

    const template = templates.find(
        (item) => String(item.id) === fields.shift_template_id,
    );
    const visibleOtherDays = useMemo(() => {
        const userId = Number(fields.user_id);
        const seen = new Set<string>();
        const sourceDays = assignment ? otherDays : scheduledDays;

        return sourceDays.filter((day) => {
            const key = `${day.assignment_id}-${day.date}-${day.shift_template_id}`;

            if (seen.has(key)) {
                return false;
            }

            seen.add(key);

            if (assignment && day.assignment_id === assignment.id) {
                return false;
            }

            if (userId && day.user_id !== userId) {
                return false;
            }

            if (fields.start_date && day.date < fields.start_date) {
                return false;
            }

            if (fields.end_date && day.date > fields.end_date) {
                return false;
            }

            return Boolean(userId);
        });
    }, [
        assignment,
        fields.end_date,
        fields.start_date,
        fields.user_id,
        otherDays,
        scheduledDays,
    ]);

    const formProps = assignment
        ? ShiftAssignmentController.update.form({
              branch: branch.slug,
              user_shift_assignment: assignment.id,
          })
        : ShiftAssignmentController.store.form(branch.slug);

    return (
        <>
            <Head
                title={assignment ? 'Edit assignment' : 'Assign to shift'}
            />

            <div className="flex w-full flex-col gap-6 p-2 md:p-4">
                <ShiftPageHeader
                    kicker="Coverage"
                    title={assignment ? 'Edit assignment' : 'Assign to shift'}
                    description="Pick the person, the dates they work, and the usual shift. Change a day only when they are off or covering someone else."
                />

                <Form
                    {...formProps}
                    className="flex flex-col gap-6"
                    options={{ preserveScroll: true }}
                    onSuccess={() => {
                        if (assignment) {
                            return;
                        }

                        setFields({
                            ...emptyShiftAssignmentFields(),
                            branch_id: String(branch.id),
                        });
                        setExceptions([]);
                    }}
                >
                    {({ errors, processing }) => (
                        <>
                            <div className="border-border/80 bg-card space-y-6 rounded-2xl border p-5 shadow-sm sm:p-6">
                                <ShiftAssignmentFields
                                    value={fields}
                                    people={people}
                                    templates={templates}
                                    branches={[branch]}
                                    lockBranch
                                    lockPerson={Boolean(assignment)}
                                    errors={errors}
                                    onChange={(patch) =>
                                        setFields((current) => ({
                                            ...current,
                                            ...patch,
                                            branch_id: String(branch.id),
                                        }))
                                    }
                                />
                            </div>

                            <ShiftAssignmentCalendar
                                startDate={fields.start_date}
                                endDate={fields.end_date}
                                workingDays={fields.days_of_week}
                                defaultTemplate={template}
                                templates={templates}
                                exceptions={exceptions}
                                otherDays={visibleOtherDays}
                                errors={errors}
                                onChange={setExceptions}
                            />

                            <div className="flex items-center gap-3">
                                <Button type="submit" disabled={processing}>
                                    {processing ? <Spinner /> : null}
                                    {assignment
                                        ? 'Save assignment'
                                        : 'Assign shift'}
                                </Button>
                                <Button variant="secondary" asChild>
                                    <Link
                                        href={ShiftTemplateController.index.url(
                                            branch.slug,
                                        )}
                                    >
                                        Back to shifts
                                    </Link>
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}
