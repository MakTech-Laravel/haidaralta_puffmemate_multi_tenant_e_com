import { Form, Head, Link, setLayoutProps } from '@inertiajs/react';
import { useState } from 'react';
import ShiftAssignmentController from '@/actions/App/Http/Controllers/Branch/ShiftAssignmentController';
import ShiftTemplateController from '@/actions/App/Http/Controllers/Branch/ShiftTemplateController';
import {
    emptyShiftAssignmentFields,
    ShiftAssignmentFields,
} from '@/components/admin/shift-assignment-fields';
import { ShiftAssignmentPreview } from '@/components/admin/shift-assignment-preview';
import { ShiftPageHeader } from '@/components/admin/shift-page-header';
import type {
    AssignablePerson,
    ShiftPeriodOption,
    ShiftTemplateOption,
} from '@/components/admin/workforce-types';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';

type Branch = { id: number; name: string; slug: string };

export default function BranchShiftsAssign({
    branch,
    people,
    templates,
    periods,
}: {
    branch: Branch;
    people: AssignablePerson[];
    templates: ShiftTemplateOption[];
    periods: ShiftPeriodOption[];
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
                title: 'Assign',
                href: ShiftAssignmentController.create.url(branch.slug),
            },
        ],
    });

    const [fields, setFields] = useState(() => ({
        ...emptyShiftAssignmentFields(),
        branch_id: String(branch.id),
    }));

    const person = people.find((item) => String(item.id) === fields.user_id);
    const template = templates.find(
        (item) => String(item.id) === fields.shift_template_id,
    );
    const period = periods.find(
        (item) => String(item.id) === fields.shift_roster_period_id,
    );

    return (
        <>
            <Head title="Assign to shift" />

            <div className="flex w-full flex-col gap-6 p-2 md:p-4">
                <ShiftPageHeader
                    kicker="Coverage"
                    title="Assign to shift"
                    description={`Place a person onto a duty window at ${branch.name}.`}
                />

                <Form
                    {...ShiftAssignmentController.store.form(branch.slug)}
                    className="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_19rem]"
                    options={{ preserveScroll: true }}
                    onSuccess={() =>
                        setFields({
                            ...emptyShiftAssignmentFields(),
                            branch_id: String(branch.id),
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
                                    branches={[branch]}
                                    lockBranch
                                    errors={errors}
                                    onChange={(patch) =>
                                        setFields((current) => ({
                                            ...current,
                                            ...patch,
                                            branch_id: String(branch.id),
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
                                            href={ShiftTemplateController.index.url(
                                                branch.slug,
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
