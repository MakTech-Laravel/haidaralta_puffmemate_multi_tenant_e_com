import { Head } from '@inertiajs/react';

import ConversationController from '@/actions/App/Http/Controllers/Platform/ConversationController';
import { ConversationWorkspace } from '@/components/conversation';
import type {
    ConversationRow,
    ConversationThreadProps,
    InboxMeta,
} from '@/components/conversation/types';

/**
 * One page for the whole inbox.
 *
 * `platform.conversations.index` and `.show` both render this component, so
 * opening a thread only swaps the right pane.
 */
export default function PlatformConversations({
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
    return (
        <>
            <Head
                title={
                    thread
                        ? `Order ${thread.conversation.order?.order_number ?? ''}`
                        : 'Conversations'
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
                    orderOptionsUrl={ConversationController.orderOptions.url()}
                    storeUrl={ConversationController.store.url()}
                    hrefFor={(row) => ConversationController.show.url(row.id)}
                    deliveredUrlFor={(id) =>
                        ConversationController.delivered.url(id)
                    }
                />
            </div>
        </>
    );
}

PlatformConversations.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/platform/dashboard' },
        { title: 'Conversations', href: '/platform/conversations' },
    ],
};
