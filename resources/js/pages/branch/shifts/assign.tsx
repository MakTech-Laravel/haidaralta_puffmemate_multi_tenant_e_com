import { Form, Head, Link, setLayoutProps } from '@inertiajs/react';
import { useState } from 'react';
import ShiftAssignmentController from '@/actions/App/Http/Controllers/Branch/ShiftAssignmentController';
import ShiftTemplateController from '@/actions/App/Http/Controllers/Branch/ShiftTemplateController';
import {
    emptyShiftAssignmentFields,
    ShiftAssignmentFields,
} from '@/components/admin/shift-assignment-fields';
import type {
    AssignablePerson,
    ShiftPeriodOption,
    ShiftTemplateOption,
} from '@/components/admin/workforce-types';
import Heading from '@/components/heading';
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

    return (
        <>
            <Head title="Assign to shift" />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <Heading
                    title="Assign to shift"
                    description={`Duty windows for people at ${branch.name}`}
                />

                <Form
                    {...ShiftAssignmentController.store.form(branch.slug)}
                    className="border-border/80 bg-card space-y-6 rounded-2xl border p-5 shadow-sm sm:p-6"
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
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}
