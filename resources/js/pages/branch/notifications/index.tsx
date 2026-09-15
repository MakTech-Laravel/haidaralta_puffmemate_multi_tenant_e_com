import { setLayoutProps } from '@inertiajs/react';
import { index } from '@/actions/App/Http/Controllers/Branch/NotificationController';
import { NotificationsInbox } from '@/components/portal/notifications-inbox';
import { useBranch } from '@/hooks/use-branch';

export default function BranchNotificationsIndex() {
    const branch = useBranch();

    setLayoutProps({
        breadcrumbs: [
            {
                title: 'Notifications',
                href: index.url(branch.slug),
            },
        ],
    });

    return <NotificationsInbox />;
}
