import { Form, Head, Link, router, setLayoutProps } from '@inertiajs/react';
import { useState } from 'react';
import {
    emptyShiftAssignmentFields,
    ShiftAssignmentFields,
} from '@/components/admin/shift-assignment-fields';
import { ShiftAssignmentPreview } from '@/components/admin/shift-assignment-preview';
import { ShiftBranchPicker } from '@/components/admin/shift-branch-picker';
import {
    platformShiftWorkspace,
    shiftBranchQuery,
    type ShiftWorkspace,
} from '@/components/admin/platform-shift-workspace';
import { ShiftPageHeader } from '@/components/admin/shift-page-header';
import type {
    AssignablePerson,
    BranchOption,
    ShiftPeriodOption,
    ShiftTemplateOption,
} from '@/components/admin/workforce-types';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';

export default function PlatformShiftsAssign({
    workspace = 'platform',
    people,
    templates,
    periods,
    branches = [],
    selectedBranchId = null,
    selectedBranch = null,
}: {
    workspace?: ShiftWorkspace;
    people: AssignablePerson[];
    templates: ShiftTemplateOption[];
    periods: ShiftPeriodOption[];
    branches?: BranchOption[];
    selectedBranchId?: number | null;
    selectedBranch?: BranchOption | null;
}) {
    const routes = platformShiftWorkspace(workspace);
    const branchQuery = shiftBranchQuery(selectedBranchId);
    const lockBranch = workspace === 'branch' && Boolean(selectedBranchId);
    const branchOptionsUrl =
        workspace === 'branch' ? routes.templates.branchOptions.url() : null;
    const [fields, setFields] = useState(() => ({
        ...emptyShiftAssignmentFields(),
        branch_id: selectedBranchId ? String(selectedBranchId) : '',
    }));

    setLayoutProps({
        breadcrumbs: [
            { title: 'Dashboard', href: '/platform/dashboard' },
            {
                title: routes.title,
                href: routes.templates.index.url(branchQuery),
            },
            {
                title: 'Assign',
                href: routes.assignments.create.url(branchQuery),
            },
        ],
    });

    const person = people.find((item) => String(item.id) === fields.user_id);
    const template = templates.find(
        (item) => String(item.id) === fields.shift_template_id,
    );
    const period = periods.find(
        (item) => String(item.id) === fields.shift_roster_period_id,
    );
    const branch =
        selectedBranch ??
        branches.find((item) => String(item.id) === fields.branch_id);

    return (
        <>
            <Head title="Assign to shift" />

            <div className="flex w-full flex-col gap-6 p-2 md:p-4">
                <ShiftPageHeader
                    kicker="Coverage"
                    title="Assign to shift"
                    description={
                        workspace === 'branch'
                            ? 'Pick a branch, then place a person on that store shift.'
                            : 'Place a person onto a platform-owned shift. Branch shifts live under Branch management.'
                    }
                />

                {workspace === 'branch' && branchOptionsUrl ? (
                    <ShiftBranchPicker
                        optionsUrl={branchOptionsUrl}
                        value={
                            selectedBranchId ? String(selectedBranchId) : ''
                        }
                        selectedLabel={selectedBranch?.name}
                        onChange={(branchId) =>
                            router.get(
                                routes.assignments.create.url({
                                    query: { branch_id: Number(branchId) },
                                }),
                            )
                        }
                    />
                ) : null}

                <Form
                    {...routes.assignments.store.form()}
                    className="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_19rem]"
                    options={{ preserveScroll: true }}
                    onSuccess={() =>
                        setFields({
                            ...emptyShiftAssignmentFields(),
                            branch_id: selectedBranchId
                                ? String(selectedBranchId)
                                : '',
                        })
                    }
                >
                    {({ errors, processing }) => (
                        <>
                            <div className="border-border/80 bg-card space-y-6 rounded-2xl border p-5 shadow-sm sm:p-6">
                                <ShiftAssignmentFields
                                    value={fields}
                                    people={people}
                                    templates={templates}
                                    periods={periods}
                                    branches={branches}
                                    lockBranch={lockBranch}
                                    errors={errors}
                                    onChange={(patch) =>
                                        setFields((current) => ({
                                            ...current,
                                            ...patch,
                                            ...(lockBranch
                                                ? {
                                                      branch_id:
                                                          String(
                                                              selectedBranchId,
                                                          ),
                                                  }
                                                : {}),
                                        }))
                                    }
                                />
                                <div className="flex items-center gap-3">
                                    <Button type="submit" disabled={processing}>
                                        {processing ? <Spinner /> : null}
                                        Assign shift
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
                            </div>

                            <ShiftAssignmentPreview
                                person={person}
                                template={template}
                                period={period}
                                branch={branch}
                            />
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}
