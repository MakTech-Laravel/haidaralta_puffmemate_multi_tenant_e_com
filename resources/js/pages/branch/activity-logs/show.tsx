import ActivityLogController from '@/actions/App/Http/Controllers/Branch/ActivityLogController';
import { ActivityLogDetail } from '@/components/activity-log/activity-log-detail';
import type { ActivityDetail } from '@/components/activity-log/types';

export default function BranchActivityLogsShow({
    branch,
    activity,
}: {
    branch: { id: number; name: string; slug: string };
    activity: ActivityDetail;
}) {
    return (
        <ActivityLogDetail
            activity={activity}
            backUrl={ActivityLogController.index.url(branch.slug)}
        />
    );
}

BranchActivityLogsShow.layout = (props: { branch: { slug: string } }) => ({
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: `/branch/${props.branch.slug}/dashboard`,
        },
        {
            title: 'Activity log',
            href: `/branch/${props.branch.slug}/activity-logs`,
        },
        { title: 'Detail', href: '#' },
    ],
});
