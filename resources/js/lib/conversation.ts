import type {
    ConversationMessage,
    OrderOption,
} from '@/components/conversation/types';

function xsrfToken(): string {
    const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);

    return match ? decodeURIComponent(match[1]) : '';
}

function jsonHeaders(): Record<string, string> {
    return {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-XSRF-TOKEN': xsrfToken(),
    };
}

export class ConversationRequestError extends Error {
    constructor(
        message: string,
        public readonly status: number,
    ) {
        super(message);
        this.name = 'ConversationRequestError';
    }
}

/**
 * Pull the page of messages immediately older than `before`.
 */
export async function fetchOlderMessages(
    endpoint: string,
    before: number,
    signal?: AbortSignal,
): Promise<{ messages: ConversationMessage[]; hasMore: boolean }> {
    const url = new URL(endpoint, window.location.origin);
    url.searchParams.set('before', String(before));

    const response = await fetch(url.toString(), {
        headers: jsonHeaders(),
        credentials: 'same-origin',
        signal,
    });

    if (!response.ok) {
        throw new ConversationRequestError(
            'Could not load earlier messages.',
            response.status,
        );
    }

    return response.json();
}

/**
 * Send a message. Files require multipart, so the body is always FormData.
 */
export async function sendMessage(
    endpoint: string,
    body: string,
    files: File[],
): Promise<ConversationMessage> {
    const payload = new FormData();

    if (body.trim() !== '') {
        payload.append('body', body.trim());
    }

    files.forEach((file) => payload.append('attachments[]', file));

    const response = await fetch(endpoint, {
        method: 'POST',
        headers: jsonHeaders(),
        credentials: 'same-origin',
        body: payload,
    });

    if (!response.ok) {
        throw new ConversationRequestError(
            await readErrorMessage(response),
            response.status,
        );
    }

    const data: { message: ConversationMessage } = await response.json();

    return data.message;
}

/**
 * Move the viewer's read marker. Fire-and-forget: a failure is not worth a toast.
 */
export async function markConversationRead(
    endpoint: string,
    messageId: number,
): Promise<void> {
    await fetch(endpoint, {
        method: 'POST',
        headers: { ...jsonHeaders(), 'Content-Type': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify({ message_id: messageId }),
    }).catch(() => undefined);
}

/**
 * Acknowledge that a message reached this client, which is what turns the
 * sender's single tick into a double tick. Fire-and-forget.
 */
export async function acknowledgeDelivery(
    endpoint: string,
    messageId: number,
): Promise<void> {
    await fetch(endpoint, {
        method: 'POST',
        headers: { ...jsonHeaders(), 'Content-Type': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify({ message_id: messageId }),
    }).catch(() => undefined);
}

export async function fetchOrderOptions(
    endpoint: string,
    search: string,
    signal?: AbortSignal,
): Promise<OrderOption[]> {
    const url = new URL(endpoint, window.location.origin);

    if (search.trim() !== '') {
        url.searchParams.set('search', search.trim());
    }

    const response = await fetch(url.toString(), {
        headers: jsonHeaders(),
        credentials: 'same-origin',
        signal,
    });

    if (!response.ok) {
        throw new ConversationRequestError(
            'Could not load orders.',
            response.status,
        );
    }

    const data: { options: OrderOption[] } = await response.json();

    return data.options;
}

/**
 * Laravel returns 422 with a validation bag and 429 for the send rate limit.
 */
async function readErrorMessage(response: Response): Promise<string> {
    if (response.status === 429) {
        return 'You are sending messages too quickly. Wait a moment and try again.';
    }

    try {
        const data: { message?: string; errors?: Record<string, string[]> } =
            await response.json();

        const firstError = data.errors
            ? Object.values(data.errors)[0]?.[0]
            : undefined;

        return firstError ?? data.message ?? 'Could not send the message.';
    } catch {
        return 'Could not send the message.';
    }
}
