---
paths:
  - 'app/Services/Conversation/**,app/Events/Conversation*,resources/js/components/conversation/**,resources/js/hooks/use-chat-scroll.ts'
---

# Js Hooks

## Conversation receipts and transcript scrolling
Delivery/read state is NOT stored per message. `conversation_participants` holds two monotonic markers (`last_delivered_message_id`, `last_read_message_id`) and the client derives a message's tick by comparing its id against every other participant's markers. That keeps the payload O(participants). `ConversationReceiptsUpdated` broadcasts the whole marker set on the presence channel, and only when a marker actually moved -- a no-op ack must not broadcast. Reading implies delivery, so `markRead` drags the delivered marker forward too.

Recipients ack delivery from the thread channel AND from the per-user inbox channel, so someone who is online but looking at a different thread still produces a double tick.

Do not use the shadcn `MessageScroller` for the transcript: it anchors to the first item, which produced a bottom gap and a jump to the top on send. Use `use-chat-scroll.ts` instead. Three invariants it protects: `justify-end` inside a `min-h-full` track keeps a short thread on the composer; arrivals only auto-scroll when the reader is already within 120px of the live edge; prepending history restores scrollTop by the scrollHeight delta.

The composer is never busy. `onSend` is synchronous, pushes an optimistic row with a negative client id, and returns immediately. Sending/failed/sent/delivered/read all render on the bubble, with Retry and Discard on failure.
