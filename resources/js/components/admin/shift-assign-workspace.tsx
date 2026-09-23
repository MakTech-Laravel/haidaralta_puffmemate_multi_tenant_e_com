import { Head, Link, router, usePage } from '@inertiajs/react';
import { CalendarCheck, UserRound } from 'lucide-react';
import { useEffect, useReducer, useRef, useState } from 'react';
import { ConfirmDeleteDialog } from '@/components/admin/confirm-delete-dialog';
import { ShiftAssignmentGate } from '@/components/admin/shift-assignment-gate';
import {
    emptyShiftAssignmentSelection,
    hasPendingShiftChanges,
    shiftAssignmentSelectionReducer,
} from '@/components/admin/shift-assignment-selection';
import { ShiftChangeDialog } from '@/components/admin/shift-change-dialog';
import { ShiftMonthCalendar } from '@/components/admin/shift-month-calendar';
import { ShiftPageHeader } from '@/components/admin/shift-page-header';
import type {
    BranchOption,
    ShiftAssignmentChangePayload,
    ShiftAssignmentPeopleKind,
    ShiftAssignmentWorkspaceProps,
    ShiftAssignmentWorkspaceQuery,
} from '@/components/admin/workforce-types';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { initials } from '@/lib/shift-format';

type WorkspaceUrlBuilder = (query: ShiftAssignmentWorkspaceQuery) => string;

export function ShiftAssignWorkspace({
    kicker,
    branch,
    branchLocked,
    branchOptionsUrl,
    peopleOptionsUrl,
    createUrl,
    storeUrl,
    shiftsUrl,
    assignedPeopleUrl,
    includeBranchInPayload,
    selectedPeopleKind,
    selectedPerson,
    templates,
    calendar,
}: {
    kicker: string;
    branch: BranchOption | null;
    branchLocked: boolean;
    branchOptionsUrl: string | null;
    peopleOptionsUrl: string | null;
    createUrl: WorkspaceUrlBuilder;
    storeUrl: string;
    shiftsUrl: string;
    assignedPeopleUrl: string;
    includeBranchInPayload: boolean;
} & Omit<
    ShiftAssignmentWorkspaceProps,
    'selectedBranch' | 'assignmentGroups'
>) {
    const { shifts } = usePage().props;
    const [selection, dispatch] = useReducer(
        shiftAssignmentSelectionReducer,
        undefined,
        emptyShiftAssignmentSelection,
    );
    const [dialogOpen, setDialogOpen] = useState(false);
    const [discardOpen, setDiscardOpen] = useState(false);
    const [targetShiftTemplateId, setTargetShiftTemplateId] = useState<
        number | null
    >(null);
    const [processing, setProcessing] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const allowNextNavigationRef = useRef(false);
    const pendingActionRef = useRef<(() => void) | null>(null);
    const pending = hasPendingShiftChanges(selection);
    const currentMonth = calendar?.month;

    useEffect(() => {
        dispatch({ type: 'reset' });
        setDialogOpen(false);
        setDiscardOpen(false);
        pendingActionRef.current = null;
        setTargetShiftTemplateId(null);
        setErrors({});
    }, [branch?.id, selectedPeopleKind, selectedPerson?.id, currentMonth]);

    useEffect(() => {
        if (!pending) {
            return;
        }

        const removeBeforeListener = router.on('before', (event) => {
            if (allowNextNavigationRef.current) {
                allowNextNavigationRef.current = false;

                return true;
            }

            const visit = event.detail.visit;

            pendingActionRef.current = () => {
                allowNextNavigationRef.current = true;
                router.visit(visit.url, {
                    method: visit.method,
                    data: visit.data,
                    replace: visit.replace,
                    preserveScroll: visit.preserveScroll,
                    preserveState: visit.preserveState,
                });
            };
            setDiscardOpen(true);

            return false;
        });
        const handleBeforeUnload = (event: BeforeUnloadEvent): void => {
            event.preventDefault();
        };

        window.addEventListener('beforeunload', handleBeforeUnload);

        return () => {
            removeBeforeListener();
            window.removeEventListener('beforeunload', handleBeforeUnload);
        };
    }, [pending]);

    const requestDiscard = (action: () => void): void => {
        if (!pending) {
            action();

            return;
        }

        pendingActionRef.current = action;
        setDiscardOpen(true);
    };

    const confirmDiscard = (): void => {
        const action = pendingActionRef.current;
        pendingActionRef.current = null;
        dispatch({ type: 'reset' });
        allowNextNavigationRef.current = true;
        action?.();
    };

    const visitWorkspace = (query: ShiftAssignmentWorkspaceQuery): void => {
        allowNextNavigationRef.current = true;
        router.get(
            createUrl(query),
            {},
            { preserveScroll: true, replace: true },
        );
    };

    const baseQuery = (): ShiftAssignmentWorkspaceQuery => ({
        ...(branch ? { branch_id: branch.id } : {}),
        ...(selectedPeopleKind ? { people_kind: selectedPeopleKind } : {}),
        ...(selectedPerson ? { user_id: selectedPerson.id } : {}),
        ...(calendar ? { month: calendar.month } : {}),
    });

    const handleBranchChange = (branchId: number | null): void => {
        requestDiscard(() =>
            visitWorkspace(branchId ? { branch_id: branchId } : {}),
        );
    };

    const handlePeopleKindChange = (
        peopleKind: ShiftAssignmentPeopleKind | null,
    ): void => {
        if (branch === null) {
            return;
        }

        requestDiscard(() =>
            visitWorkspace({
                branch_id: branch.id,
                ...(peopleKind ? { people_kind: peopleKind } : {}),
            }),
        );
    };

    const handlePersonChange = (userId: number | null): void => {
        if (branch === null || selectedPeopleKind === null) {
            return;
        }

        requestDiscard(() =>
            visitWorkspace({
                branch_id: branch.id,
                people_kind: selectedPeopleKind,
                ...(userId ? { user_id: userId } : {}),
            }),
        );
    };

    const openSaveDialog = (): void => {
        setErrors({});
        setDialogOpen(true);
    };

    const submitChanges = (): void => {
        if (selectedPerson === null || branch === null || !pending) {
            return;
        }

        const payload: ShiftAssignmentChangePayload = {
            ...(includeBranchInPayload ? { branch_id: branch.id } : {}),
            user_id: selectedPerson.id,
            shift_template_id:
                selection.assignDates.size > 0 ||
                selection.replaceDayIds.size > 0
                    ? targetShiftTemplateId
                    : null,
            assign_dates: [...selection.assignDates].sort(),
            replace_day_ids: [...selection.replaceDayIds].sort(
                (left, right) => left - right,
            ),
            remove_day_ids: [...selection.removeDayIds].sort(
                (left, right) => left - right,
            ),
            ...(calendar ? { month: calendar.month } : {}),
        };

        allowNextNavigationRef.current = true;
        router.post(storeUrl, payload, {
            preserveScroll: 'errors',
            onStart: () => setProcessing(true),
            onError: (responseErrors) => {
                allowNextNavigationRef.current = false;
                setErrors(
                    Object.fromEntries(
                        Object.entries(responseErrors).map(([key, value]) => [
                            key,
                            String(value),
                        ]),
                    ),
                );
            },
            onSuccess: () => {
                dispatch({ type: 'reset' });
                setDialogOpen(false);
                setTargetShiftTemplateId(null);
                setErrors({});
            },
            onFinish: () => {
                allowNextNavigationRef.current = false;
                setProcessing(false);
            },
        });
    };

    return (
        <>
            <Head title="Assign shifts" />
            <div className="flex w-full flex-col gap-6 p-2 md:p-4">
                <ShiftPageHeader
                    kicker={kicker}
                    title="Assign shifts"
                    description="Choose a branch and person, stage calendar changes, then save everything together."
                    actions={
                        <div className="flex flex-wrap gap-2">
                            <Button variant="outline" asChild>
                                <Link href={shiftsUrl}>Back to shifts</Link>
                            </Button>
                            <Button variant="secondary" asChild>
                                <Link href={assignedPeopleUrl}>
                                    Assigned people
                                </Link>
                            </Button>
                        </div>
                    }
                />

                <ShiftAssignmentGate
                    branch={branch}
                    branchLocked={branchLocked}
                    branchOptionsUrl={branchOptionsUrl}
                    peopleKind={selectedPeopleKind}
                    personId={selectedPerson?.id ?? null}
                    personName={selectedPerson?.name}
                    peopleOptionsUrl={peopleOptionsUrl}
                    onBranchChange={handleBranchChange}
                    onPeopleKindChange={handlePeopleKindChange}
                    onPersonChange={handlePersonChange}
                />

                {selectedPerson && calendar ? (
                    <>
                        <section className="border-primary/20 bg-primary/5 flex items-center gap-3 rounded-xl border p-4">
                            <Avatar className="size-11">
                                <AvatarImage
                                    src={selectedPerson.avatar_url ?? undefined}
                                    alt=""
                                />
                                <AvatarFallback>
                                    {initials(selectedPerson.name)}
                                </AvatarFallback>
                            </Avatar>
                            <div className="min-w-0">
                                <p className="truncate font-semibold">
                                    {selectedPerson.name}
                                </p>
                                <p className="text-muted-foreground text-sm capitalize">
                                    {selectedPeopleKind} · {branch?.name}
                                </p>
                            </div>
                        </section>

                        <ShiftMonthCalendar
                            calendar={calendar}
                            selection={selection}
                            allowMultiplePerDay={shifts.allow_multiple_per_day}
                            dispatch={dispatch}
                            onMonthChange={(month) => {
                                requestDiscard(() =>
                                    visitWorkspace({
                                        ...baseQuery(),
                                        month,
                                    }),
                                );
                            }}
                        />

                        <div className="border-border bg-card sticky bottom-3 z-20 flex flex-col gap-3 rounded-xl border p-3 shadow-lg sm:flex-row sm:items-center sm:justify-between">
                            <div className="text-sm">
                                <strong>
                                    {selection.assignDates.size +
                                        selection.replaceDayIds.size +
                                        selection.removeDayIds.size}
                                </strong>{' '}
                                pending changes
                                <span className="text-muted-foreground ml-2">
                                    {selection.assignDates.size} added ·{' '}
                                    {selection.replaceDayIds.size} changed ·{' '}
                                    {selection.removeDayIds.size} removed
                                </span>
                            </div>
                            <Button
                                type="button"
                                disabled={!pending}
                                onClick={openSaveDialog}
                            >
                                <CalendarCheck /> Save changes
                            </Button>
                        </div>
                    </>
                ) : (
                    <section className="border-border bg-muted/20 flex min-h-64 flex-col items-center justify-center gap-3 rounded-2xl border border-dashed p-8 text-center">
                        <UserRound className="text-muted-foreground size-8" />
                        <div>
                            <h2 className="font-semibold">
                                Choose who to schedule
                            </h2>
                            <p className="text-muted-foreground mt-1 max-w-md text-sm">
                                Select the branch, user type, and user above to
                                load their calendar.
                            </p>
                        </div>
                    </section>
                )}
            </div>

            <ShiftChangeDialog
                open={dialogOpen}
                templates={templates}
                targetShiftTemplateId={targetShiftTemplateId}
                addedCount={selection.assignDates.size}
                changedCount={selection.replaceDayIds.size}
                removedCount={selection.removeDayIds.size}
                errors={errors}
                processing={processing}
                onOpenChange={(open) => {
                    if (!processing) {
                        setDialogOpen(open);
                    }
                }}
                onTargetChange={setTargetShiftTemplateId}
                onConfirm={submitChanges}
            />

            <ConfirmDeleteDialog
                open={discardOpen}
                onOpenChange={(open) => {
                    setDiscardOpen(open);

                    if (!open) {
                        pendingActionRef.current = null;
                    }
                }}
                title="Discard unsaved changes?"
                description="Your staged shift changes will be lost if you continue."
                confirmLabel="Discard changes"
                onConfirm={confirmDiscard}
            />
        </>
    );
}
