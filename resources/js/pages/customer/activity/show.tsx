import ActivityLogController from '@/actions/App/Http/Controllers/Customer/ActivityLogController';
import { ActivityLogDetail } from '@/components/activity-log/activity-log-detail';
import type { ActivityDetail } from '@/components/activity-log/types';

export default function CustomerActivityShow({
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

CustomerActivityShow.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'My activity', href: '/activity' },
        { title: 'Detail', href: '#' },
    ],
};
