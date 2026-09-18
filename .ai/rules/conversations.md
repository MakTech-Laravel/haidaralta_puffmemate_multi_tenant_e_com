---
paths:
    - '{app/Support/ConversationInbox.php,app/Http/Controllers/*/ConversationController.php,resources/js/components/conversation/**,resources/js/pages/*/conversations/**}'
---

# Conversations

## Conversations use a messenger workspace, not a DataTable

The conversation inbox is deliberately NOT a Spatie DataTable — it is a messenger list (avatar, last-message preview, unread badge, infinite scroll on last_message_at, Meilisearch free text). Build it with App\Support\ConversationInbox, not AbstractDataTable.

The index and show routes render the SAME Inertia component (platform|branch/conversations/index). Opening a thread is a partial visit with only:['thread'], so the left list keeps its scroll, search term and Echo subscription instead of remounting. Never add a separate show.tsx page.

The inbox list is an Inertia merge prop (`conversations`, matchOn id) with meta in `inboxMeta`; page it with router.reload({only:['conversations','inboxMeta'], data:{cursor}}) and pass reset:['conversations'] whenever the search or status filter changes.

conversation.{id} is a PRESENCE channel: one subscription carries MessageCreated, ConversationClosed, the online roster and `typing` whispers. Each participant also has a private conversation-inbox.{userId} channel so the list re-badges for threads that are not open. Unread counts come from App\Support\ConversationUnreadCounts (one grouped query cached in Redis) — never count per row.
