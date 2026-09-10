import ActivityLogController from '@/actions/App/Http/Controllers/Platform/ActivityLogController';
import { ActivityLogDetail } from '@/components/activity-log/activity-log-detail';
import type { ActivityDetail } from '@/components/activity-log/types';

export default function PlatformActivityLogsShow({
    activity,
}: {
    activity: ActivityDetail;
}) {
    return (
        <ActivityLogDetail
            activity={activity}
            backUrl={ActivityLogController.index.url()}
        />
    );
}

PlatformActivityLogsShow.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Activity log', href: '/platform/activity-logs' },
        { title: 'Detail', href: '#' },
    ],
};
