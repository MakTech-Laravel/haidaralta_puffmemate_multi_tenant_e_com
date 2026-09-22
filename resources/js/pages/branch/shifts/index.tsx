import { Head, setLayoutProps } from '@inertiajs/react';
import ShiftAssignmentController from '@/actions/App/Http/Controllers/Branch/ShiftAssignmentController';
import ShiftTemplateController from '@/actions/App/Http/Controllers/Branch/ShiftTemplateController';
import { ShiftBoard } from '@/components/admin/shift-board';
import type {
    ShiftAssignmentRow,
    ShiftTemplateOption,
} from '@/components/admin/workforce-types';

type Branch = { id: number; name: string; slug: string };

export default function BranchShiftsIndex({
    branch,
    templates,
    onDuty = [],
}: {
    branch: Branch;
    templates: ShiftTemplateOption[];
    onDuty?: ShiftAssignmentRow[];
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
        ],
    });

    return (
        <>
            <Head title="Shifts" />
            <ShiftBoard
                kicker={branch.name}
                title="Shifts"
                description={`Shifts at ${branch.name}. Platform shifts can be assigned here; only this store’s own shifts can be edited.`}
                templates={templates}
                onDuty={onDuty}
                createTemplateHref={ShiftTemplateController.create.url(
                    branch.slug,
                )}
                assignHref={ShiftAssignmentController.create.url(branch.slug)}
                assignedPeopleHref={ShiftAssignmentController.index.url(
                    branch.slug,
                )}
                templateEditHref={(template) =>
                    template.branch_id === branch.id
                        ? ShiftTemplateController.edit.url({
                              branch: branch.slug,
                              shift_template: template.id,
                          })
                        : null
                }
            />
        </>
    );
}
