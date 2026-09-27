import { router, setLayoutProps } from '@inertiajs/react';
import {
    destroy,
    index,
    show,
} from '@/actions/App/Http/Controllers/Branch/NotificationController';
import type { NotificationItem } from '@/components/notifications/types';
import { NotificationsInbox } from '@/components/portal/notifications-inbox';
import { useBranch } from '@/hooks/use-branch';

export default function BranchNotificationsIndex({
    items = [],
}: {
    items?: NotificationItem[];
}) {
    const branch = useBranch();

    setLayoutProps({
        breadcrumbs: [
            {
                title: 'Notifications',
                href: index.url(branch.slug),
            },
        ],
    });

    return (
        <NotificationsInbox
            notifications={items}
            getItemHref={(item) =>
                show.url({
                    branch: branch.slug,
                    userNotification: Number(item.id),
                })
            }
            onDelete={(item) => {
                router.delete(
                    destroy.url({
                        branch: branch.slug,
                        userNotification: Number(item.id),
                    }),
                    { preserveScroll: true },
                );
            }}
        />
    );
}
