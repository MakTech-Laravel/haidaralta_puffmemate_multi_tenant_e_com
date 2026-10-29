import { Head } from '@inertiajs/react';

import DriverChatController from '@/actions/App/Http/Controllers/Driver/DriverChatController';
import { ConversationWorkspace } from '@/components/conversation';
import type {
    ConversationRow,
    ConversationThreadProps,
    InboxMeta,
} from '@/components/conversation/types';

/**
 * Driver-side shared chat with branch staff (no order / customer).
 */
export default function DriverChats({
    conversations,
    inboxMeta,
    thread,
    viewerId,
    viewerName,
    indexUrl,
    inboxChannel,
    storeUrl,
}: {
    conversations: ConversationRow[];
    inboxMeta: InboxMeta;
    thread: ConversationThreadProps | null;
    viewerId: number;
    viewerName: string;
    indexUrl: string;
    inboxChannel: string;
    storeUrl: string;
}) {
    return (
        <>
            <Head
                title={
                    thread
                        ? (thread.conversation.branch?.name ?? 'Branch chat')
                        : 'Chats'
                }
            />

            <div className="flex h-[calc(100svh-8rem)] flex-col p-3 sm:p-4">
                <ConversationWorkspace
                    conversations={conversations}
                    inboxMeta={inboxMeta}
                    thread={thread}
                    viewerId={viewerId}
                    viewerName={viewerName}
                    indexUrl={indexUrl}
                    inboxChannel={inboxChannel}
                    kind="driver_ops"
                    title="Branch chats"
                    showSenderRole={false}
                    canClose={false}
                    optionField="branch_id"
                    orderOptionsUrl={DriverChatController.branchOptions.url()}
                    storeUrl={storeUrl}
                    hrefFor={(row) => DriverChatController.show.url(row.id)}
                    deliveredUrlFor={(id) =>
                        DriverChatController.delivered.url(id)
                    }
                />
            </div>
        </>
    );
}

DriverChats.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/driver/dashboard' },
        { title: 'Chats', href: '/driver/chats' },
    ],
};
