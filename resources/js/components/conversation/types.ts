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
};

export type ConversationParticipant = {
    id: number;
    user_id: number;
    name: string;
    role: ParticipantRole;
    role_label: string;
    avatar_url: string | null;
    last_read_message_id: number | null;
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

export type InboxMeta = {
    hasMore: boolean;
    nextCursor: string | null;
    filters: { search: string; status: string };
    statuses: { value: string; label: string }[];
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
    status: ConversationStatus;
    last_message_preview: string | null;
    last_message_at: string | null;
};

export type TypingWhisper = {
    id: number;
    name: string;
};
