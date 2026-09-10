import ActivityLogController from '@/actions/App/Http/Controllers/Driver/ActivityLogController';
import { ActivityLogDetail } from '@/components/activity-log/activity-log-detail';
import type { ActivityDetail } from '@/components/activity-log/types';

export default function DriverActivityShow({
    activity,
}: {
    activity: ActivityDetail;
}) {
    return (
        <ActivityLogDetail
            activity={activity}
            backUrl={ActivityLogController.index.url()}
            titlePrefix="My activity"
        />
    );
}

DriverActivityShow.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/driver/dashboard' },
        { title: 'My activity', href: '/driver/activity' },
        { title: 'Detail', href: '#' },
    ],
};
