import { Head, setLayoutProps } from '@inertiajs/react';
import ShiftAssignmentController from '@/actions/App/Http/Controllers/Branch/ShiftAssignmentController';
import ShiftRosterPeriodController from '@/actions/App/Http/Controllers/Branch/ShiftRosterPeriodController';
import ShiftTemplateController from '@/actions/App/Http/Controllers/Branch/ShiftTemplateController';
import { ShiftBoard } from '@/components/admin/shift-board';
import type {
    ShiftAssignmentRow,
    ShiftPeriodOption,
    ShiftTemplateOption,
    ShiftWorkspaceStats,
} from '@/components/admin/workforce-types';

type Branch = { id: number; name: string; slug: string };

export default function BranchShiftsIndex({
    branch,
    templates,
    periods,
    onDuty = [],
    stats = {
        on_duty: 0,
        templates: 0,
        active_templates: 0,
        periods: 0,
        overnight: 0,
    },
}: {
    branch: Branch;
    templates: ShiftTemplateOption[];
    periods: ShiftPeriodOption[];
    onDuty?: ShiftAssignmentRow[];
    stats?: ShiftWorkspaceStats;
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
                periods={periods}
                onDuty={onDuty}
                stats={stats}
                createTemplateHref={ShiftTemplateController.create.url(
                    branch.slug,
                )}
                createPeriodHref={ShiftRosterPeriodController.create.url(
                    branch.slug,
                )}
                assignHref={ShiftAssignmentController.create.url(branch.slug)}
                templateEditHref={(template) =>
                    template.branch_id === branch.id
                        ? ShiftTemplateController.edit.url({
                              branch: branch.slug,
                              shift_template: template.id,
                          })
                        : null
                }
                periodEditHref={(period) =>
                    period.branch_id === branch.id
                        ? ShiftRosterPeriodController.edit.url({
                              branch: branch.slug,
                              shift_roster_period: period.id,
                          })
                        : null
                }
            />
        </>
    );
}
