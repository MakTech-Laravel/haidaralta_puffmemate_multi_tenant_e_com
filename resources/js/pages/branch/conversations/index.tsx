import { Head } from '@inertiajs/react';

import ConversationController from '@/actions/App/Http/Controllers/Branch/ConversationController';
import { ConversationWorkspace } from '@/components/conversation';
import type {
    ConversationRow,
    ConversationThreadProps,
    InboxMeta,
} from '@/components/conversation/types';
import { useBranch } from '@/hooks/use-branch';

/**
 * One page for the whole branch inbox.
 *
 * `branch.conversations.index` and `.show` both render this component, so
 * opening a thread only swaps the right pane.
 */
export default function BranchConversations({
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
                    orderOptionsUrl={ConversationController.orderOptions.url(
                        branch.slug,
                    )}
                    storeUrl={ConversationController.store.url(branch.slug)}
                    hrefFor={(row) =>
                        ConversationController.show.url([branch.slug, row.id])
                    }
                />
            </div>
        </>
    );
}

BranchConversations.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/branch/dashboard' },
        { title: 'Conversations', href: '/branch/conversations' },
    ],
};
