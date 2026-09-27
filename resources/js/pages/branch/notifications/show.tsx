import { Head, setLayoutProps } from '@inertiajs/react';
import {
    index,
    show,
} from '@/actions/App/Http/Controllers/Branch/NotificationController';
import { NotificationDetail } from '@/components/notifications/notification-detail';
import type { NotificationItem } from '@/components/notifications/types';
import { useBranch } from '@/hooks/use-branch';

type BranchNotificationShowProps = {
    notification: NotificationItem;
};

export default function BranchNotificationShow({
    notification,
}: BranchNotificationShowProps) {
    const branch = useBranch();

    setLayoutProps({
        breadcrumbs: [
            {
                title: 'Notifications',
                href: index.url(branch.slug),
            },
            {
                title: notification.title,
                href: show.url({
                    branch: branch.slug,
                    userNotification: Number(notification.id),
                }),
            },
        ],
    });

    return (
        <>
            <Head title={notification.title} />
            <div className="p-2 md:p-4">
                <NotificationDetail notification={notification} />
            </div>
        </>
    );
}
