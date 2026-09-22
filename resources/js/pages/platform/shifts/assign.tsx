import { Form, Head, Link, router, setLayoutProps } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { ShiftAssignmentCalendar } from '@/components/admin/shift-assignment-calendar';
import {
    emptyShiftAssignmentFields,
    ShiftAssignmentFields,
} from '@/components/admin/shift-assignment-fields';
import { ShiftAssignEmptyState } from '@/components/admin/shift-assign-empty-state';
import { ShiftBranchPicker } from '@/components/admin/shift-branch-picker';
import { exceptionsFromSavedDays } from '@/components/admin/shift-duty-calendar';
import {
    platformShiftWorkspace,
    shiftBranchQuery,
    type ShiftWorkspace,
} from '@/components/admin/platform-shift-workspace';
import { ShiftPageHeader } from '@/components/admin/shift-page-header';
import type {
    AssignablePerson,
    BranchOption,
    ShiftAssignmentRow,
    ShiftDayException,
    ShiftDutyDay,
    ShiftTemplateOption,
} from '@/components/admin/workforce-types';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';

export default function PlatformShiftsAssign({
    workspace = 'platform',
    people,
    templates,
    branches = [],
    selectedBranchId = null,
    selectedBranch = null,
    assignment = null,
    days = [],
    otherDays = [],
    scheduledDays = [],
}: {
    workspace?: ShiftWorkspace;
    people: AssignablePerson[];
    templates: ShiftTemplateOption[];
    branches?: BranchOption[];
    selectedBranchId?: number | null;
    selectedBranch?: BranchOption | null;
    assignment?: ShiftAssignmentRow | null;
    days?: ShiftDutyDay[];
    otherDays?: ShiftDutyDay[];
    scheduledDays?: ShiftDutyDay[];
}) {
    const routes = platformShiftWorkspace(workspace);
    const branchQuery = shiftBranchQuery(selectedBranchId);
    const lockBranch = workspace === 'branch' && Boolean(selectedBranchId);
    const branchOptionsUrl =
        workspace === 'branch' ? routes.templates.branchOptions.url() : null;
    const waitingForBranch = Boolean(
        workspace === 'branch' &&
            !assignment &&
            !selectedBranchId &&
            branchOptionsUrl,
    );
    const [fields, setFields] = useState(() => ({
        ...emptyShiftAssignmentFields(),
        user_id: assignment ? String(assignment.user_id) : '',
        branch_id: assignment
            ? String(assignment.branch_id)
            : selectedBranchId
              ? String(selectedBranchId)
              : '',
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

    setLayoutProps({
        breadcrumbs: [
            { title: 'Dashboard', href: '/platform/dashboard' },
            {
                title: routes.title,
                href: routes.templates.index.url(branchQuery),
            },
            {
                title: assignment ? 'Edit assignment' : 'Assign',
                href: assignment
                    ? routes.assignments.edit.url(assignment.id, branchQuery)
                    : routes.assignments.create.url(branchQuery),
            },
        ],
    });

    const template = templates.find(
        (item) => String(item.id) === fields.shift_template_id,
    );
    const visibleOtherDays = useMemo(() => {
        const userId = Number(fields.user_id);
        const branchId = Number(fields.branch_id);
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

            if (branchId && day.branch_id !== branchId) {
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
        fields.branch_id,
        fields.end_date,
        fields.start_date,
        fields.user_id,
        otherDays,
        scheduledDays,
    ]);

    const formProps = assignment
        ? routes.assignments.update.form(assignment.id)
        : routes.assignments.store.form();

    return (
        <>
            <Head
                title={assignment ? 'Edit assignment' : 'Assign to shift'}
            />

            <div
                className={
                    waitingForBranch
                        ? 'flex w-full flex-col gap-5 p-2 md:p-4'
                        : 'flex w-full flex-col gap-6 p-2 md:p-4'
                }
            >
                <ShiftPageHeader
                    kicker="Coverage"
                    title={assignment ? 'Edit assignment' : 'Assign to shift'}
                    description={
                        waitingForBranch
                            ? 'Choose a branch to assign someone at that store.'
                            : 'Pick the person, the dates they work, and the usual shift. Change a day only when they are off or covering someone else.'
                    }
                />

                {waitingForBranch && branchOptionsUrl ? (
                    <ShiftAssignEmptyState
                        picker={
                            <ShiftBranchPicker
                                optionsUrl={branchOptionsUrl}
                                value=""
                                selectedLabel={null}
                                size="lg"
                                className="max-w-none"
                                onChange={(branchId) =>
                                    router.get(
                                        routes.assignments.create.url({
                                            query: {
                                                branch_id: Number(branchId),
                                            },
                                        }),
                                    )
                                }
                            />
                        }
                    />
                ) : (
                    <>
                        {workspace === 'branch' && branchOptionsUrl ? (
                            <ShiftBranchPicker
                                optionsUrl={branchOptionsUrl}
                                value={
                                    selectedBranchId
                                        ? String(selectedBranchId)
                                        : ''
                                }
                                selectedLabel={selectedBranch?.name}
                                onChange={(branchId) =>
                                    router.get(
                                        routes.assignments.create.url({
                                            query: {
                                                branch_id: Number(branchId),
                                            },
                                        }),
                                    )
                                }
                            />
                        ) : null}

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
                                    branch_id: selectedBranchId
                                        ? String(selectedBranchId)
                                        : '',
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
                                    branches={branches}
                                    lockBranch={lockBranch}
                                    lockPerson={Boolean(assignment)}
                                    errors={errors}
                                    onChange={(patch) =>
                                        setFields((current) => ({
                                            ...current,
                                            ...patch,
                                            ...(lockBranch
                                                ? {
                                                      branch_id: String(
                                                          selectedBranchId,
                                                      ),
                                                  }
                                                : {}),
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
                                    {assignment ? 'Save assignment' : 'Assign shift'}
                                </Button>
                                <Button variant="secondary" asChild>
                                    <Link
                                        href={routes.templates.index.url(
                                            branchQuery,
                                        )}
                                    >
                                        Back to shifts
                                    </Link>
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
                    </>
                )}
            </div>
        </>
    );
}
