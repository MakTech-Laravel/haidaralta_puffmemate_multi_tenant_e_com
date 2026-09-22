import { Head, setLayoutProps } from '@inertiajs/react';
import ShiftAssignmentController from '@/actions/App/Http/Controllers/Branch/ShiftAssignmentController';
import ShiftTemplateController from '@/actions/App/Http/Controllers/Branch/ShiftTemplateController';
import { ShiftAssignedPeoplePage } from '@/components/admin/shift-assigned-people-page';
import type { AssignedPeopleTablePayload } from '@/components/admin/shift-assigned-people-page';

type Branch = { id: number; name: string; slug: string };

export default function BranchAssignedPeople({
    branch,
    table,
}: {
    branch: Branch;
    table: AssignedPeopleTablePayload;
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
                title: 'Assigned people',
                href: ShiftAssignmentController.index.url(branch.slug),
            },
        ],
    });

    return (
        <>
            <Head title="Assigned people" />
            <ShiftAssignedPeoplePage
                kicker={branch.name}
                title="Assigned people"
                description="Everyone who has a shift assigned — their name, dates, and the shift."
                tablePayload={table}
                indexUrl={ShiftAssignmentController.index.url(branch.slug)}
                assignHref={ShiftAssignmentController.create.url(branch.slug)}
                editHref={(row) =>
                    ShiftAssignmentController.edit.url({
                        branch: branch.slug,
                        user_shift_assignment: row.id,
                    })
                }
            />
        </>
    );
}
