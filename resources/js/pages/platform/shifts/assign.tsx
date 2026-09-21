import { Form, Head, Link, setLayoutProps } from '@inertiajs/react';
import { useState } from 'react';
import {
    emptyShiftAssignmentFields,
    ShiftAssignmentFields,
} from '@/components/admin/shift-assignment-fields';
import {
    platformShiftWorkspace,
    type ShiftWorkspace,
} from '@/components/admin/platform-shift-workspace';
import type {
    AssignablePerson,
    BranchOption,
    ShiftPeriodOption,
    ShiftTemplateOption,
} from '@/components/admin/workforce-types';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';

export default function PlatformShiftsAssign({
    workspace = 'platform',
    people,
    templates,
    periods,
    branches,
}: {
    workspace?: ShiftWorkspace;
    people: AssignablePerson[];
    templates: ShiftTemplateOption[];
    periods: ShiftPeriodOption[];
    branches: BranchOption[];
}) {
    const routes = platformShiftWorkspace(workspace);
    const [fields, setFields] = useState(emptyShiftAssignmentFields());

    setLayoutProps({
        breadcrumbs: [
            { title: 'Dashboard', href: '/platform/dashboard' },
            { title: routes.title, href: routes.templates.index.url() },
            { title: 'Assign', href: routes.assignments.create.url() },
        ],
    });

    return (
        <>
            <Head title="Assign to shift" />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <Heading
                    title="Assign to shift"
                    description={
                        workspace === 'branch'
                            ? 'Assign a person onto a branch-owned duty window. Platform templates live under Administrator.'
                            : 'Assign a person onto a platform-owned duty window. Branch templates live under Branch management.'
                    }
                />

                <Form
                    {...routes.assignments.store.form()}
                    className="border-border/80 bg-card space-y-6 rounded-2xl border p-5 shadow-sm sm:p-6"
                    options={{ preserveScroll: true }}
                    onSuccess={() => setFields(emptyShiftAssignmentFields())}
                >
                    {({ errors, processing }) => (
                        <>
                            <ShiftAssignmentFields
                                value={fields}
                                people={people}
                                templates={templates}
                                periods={periods}
                                branches={branches}
                                errors={errors}
                                onChange={(patch) =>
                                    setFields((current) => ({
                                        ...current,
                                        ...patch,
                                    }))
                                }
                            />
                            <div className="flex items-center gap-3">
                                <Button type="submit" disabled={processing}>
                                    {processing ? <Spinner /> : null}
                                    Assign shift
                                </Button>
                                <Button variant="secondary" asChild>
                                    <Link href={routes.templates.index.url()}>
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
