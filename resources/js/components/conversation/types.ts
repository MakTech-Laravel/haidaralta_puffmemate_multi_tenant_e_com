export type ConversationStatus = 'open' | 'closed';

export type MessageType = 'text' | 'image' | 'file' | 'system';

export type ParticipantRole =
    | 'customer'
    | 'driver'
    | 'branch_staff'
    | 'platform_staff';

export type MessageSender = {
    id: number;
    name: string;
    avatar_url: string | null;
};

export type MessageAttachment = {
    id: number;
    name: string;
    mime: string;
    size: number;
    is_image: boolean;
    url: string;
};

export type ConversationMessage = {
    id: number;
    conversation_id: number;
    type: MessageType;
    body: string | null;
    created_at: string | null;
    sender: MessageSender | null;
    attachments: MessageAttachment[];
    /**
     * Client-only. Optimistic rows carry a negative id and live in the
     * transcript until the server replaces or rejects them.
     */
    pending?: {
        clientId: number;
        state: 'sending' | 'failed';
        body: string;
        files: File[];
    };
};

/**
 * Tick state for one of the viewer's own messages.
 *
 * Derived by comparing the message id against every other participant's
 * markers, so it costs nothing per message on the wire.
 */
export type ReceiptState = 'sending' | 'failed' | 'sent' | 'delivered' | 'read';

export type ParticipantReceipt = {
    user_id: number;
    last_delivered_message_id: number;
    last_read_message_id: number;
};

export type ConversationParticipant = {
    id: number;
    user_id: number;
    name: string;
    role: ParticipantRole;
    role_label: string;
    avatar_url: string | null;
};

export type ConversationOrder = {
    id: number;
    order_number: string;
    status: string;
    status_label: string;
};

export type ConversationSummary = {
    id: number;
    status: ConversationStatus;
    closed_at: string | null;
    last_message_at: string | null;
    channel: string;
    order: ConversationOrder | null;
    branch: { id: number; name: string; slug: string } | null;
    participants: ConversationParticipant[];
    receipts: ParticipantReceipt[];
};

export type InboxParticipant = {
    user_id: number;
    name: string;
    role_label: string;
    avatar_url: string | null;
};

export type ConversationRow = {
    id: number;
    order_number: string;
    order_status_label: string;
    status: ConversationStatus;
    branch: { id: number; name: string; slug: string } | null;
    last_message_preview: string | null;
    last_message_at: string | null;
    unread_count: number;
    participants: InboxParticipant[];
};

export type FilterOption = { value: string; label: string };

export type InboxMeta = {
    hasMore: boolean;
    nextCursor: string | null;
    filters: { search: string; status: string; branch_id: string };
    statuses: FilterOption[];
    /**
     * Label for the selected branch only. The filter combobox pages its own
     * options, so the full branch list is never shipped in the props.
     */
    branchLabel: string | null;
};

export type ConversationThreadProps = {
    conversation: ConversationSummary;
    messages: ConversationMessage[];
    hasMore: boolean;
    canSend: boolean;
    canClose: boolean;
    endpoints: ConversationEndpoints;
};

/** A participant currently subscribed to the thread's presence channel. */
export type PresenceMember = {
    id: number;
    name: string;
    avatar_url: string | null;
};

export type OrderOption = {
    value: number;
    label: string;
    description: string;
};

/**
 * Endpoints differ per portal (platform vs branch), so the thread component
 * receives them as props instead of building URLs itself.
 */
export type ConversationEndpoints = {
    self: string;
    messages: string;
    delivered: string;
    send: string;
    read: string;
    close: string;
};

export type MessageCreatedPayload = {
    message: ConversationMessage;
};

export type ConversationClosedPayload = {
    conversation_id: number;
    status: ConversationStatus;
    closed_at: string | null;
};

export type InboxUpdatedPayload = {
    conversation_id: number;
    last_message_id: number;
    sender_id: number | null;
    status: ConversationStatus;
    last_message_preview: string | null;
    last_message_at: string | null;
    row: ConversationRow;
    message: ConversationMessage | null;
};

export type TypingWhisper = {
    id: number;
    name: string;
};

export type ReceiptsUpdatedPayload = {
    conversation_id: number;
    receipts: ParticipantReceipt[];
};
