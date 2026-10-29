import { Head } from '@inertiajs/react';

import DriverChatController from '@/actions/App/Http/Controllers/Branch/DriverChatController';
import { ConversationWorkspace } from '@/components/conversation';
import type {
    ConversationRow,
    ConversationThreadProps,
    InboxMeta,
} from '@/components/conversation/types';
import { useBranch } from '@/hooks/use-branch';

/**
 * Shared branch↔driver ops inbox (no order / customer).
 */
export default function BranchDriverChats({
    conversations,
    inboxMeta,
    thread,
    viewerId,
    viewerName,
    indexUrl,
    inboxChannel,
}: {
    conversations: ConversationRow[];
    inboxMeta: InboxMeta;
    thread: ConversationThreadProps | null;
    viewerId: number;
    viewerName: string;
    indexUrl: string;
    inboxChannel: string;
}) {
    const branch = useBranch();

    return (
        <>
            <Head
                title={
                    thread
                        ? (thread.conversation.driver?.name ?? 'Driver chat')
                        : 'Driver chat'
                }
            />

            <div className="h-[calc(100svh-6rem)] p-2 md:p-4">
                <ConversationWorkspace
                    conversations={conversations}
                    inboxMeta={inboxMeta}
                    thread={thread}
                    viewerId={viewerId}
                    viewerName={viewerName}
                    indexUrl={indexUrl}
                    inboxChannel={inboxChannel}
                    kind="driver_ops"
                    title="Driver chat"
                    showSenderRole
                    canClose={false}
                    optionField="driver_id"
                    orderOptionsUrl={DriverChatController.driverOptions.url(
                        branch.slug,
                    )}
                    storeUrl={DriverChatController.store.url(branch.slug)}
                    hrefFor={(row) =>
                        DriverChatController.show.url([branch.slug, row.id])
                    }
                    deliveredUrlFor={(id) =>
                        DriverChatController.delivered.url([branch.slug, id])
                    }
                />
            </div>
        </>
    );
}

BranchDriverChats.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/branch/dashboard' },
        { title: 'Driver chat', href: '/branch/driver-chats' },
    ],
};
