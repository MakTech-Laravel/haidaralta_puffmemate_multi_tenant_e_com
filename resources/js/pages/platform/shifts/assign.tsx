import { Form, Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import ShiftAssignmentController from '@/actions/App/Http/Controllers/Platform/ShiftAssignmentController';
import ShiftTemplateController from '@/actions/App/Http/Controllers/Platform/ShiftTemplateController';
import {
    emptyShiftAssignmentFields,
    ShiftAssignmentFields,
} from '@/components/admin/shift-assignment-fields';
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
    people,
    templates,
    periods,
    branches,
}: {
    people: AssignablePerson[];
    templates: ShiftTemplateOption[];
    periods: ShiftPeriodOption[];
    branches: BranchOption[];
}) {
    const [fields, setFields] = useState(emptyShiftAssignmentFields);

    return (
        <>
            <Head title="Assign to shift" />

            <div className="flex w-full flex-col gap-4 p-2 md:p-4">
                <Heading
                    title="Assign to shift"
                    description="Give a driver or branch staff member a duty window. Platform users cannot be assigned."
                />

                <Form
                    {...ShiftAssignmentController.store.form()}
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
                                    <Link
                                        href={ShiftTemplateController.index.url()}
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

PlatformShiftsAssign.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Shifts', href: '/platform/shifts' },
        { title: 'Assign', href: '/platform/shifts/assign' },
    ],
};
