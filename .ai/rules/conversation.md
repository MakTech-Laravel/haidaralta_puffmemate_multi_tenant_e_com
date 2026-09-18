---
paths:
  - 'app/Services/Conversation/**,resources/js/components/conversation/**'
---

# Conversation

## Closing a conversation is idempotent
ConversationService::close() is a no-op on an already-closed thread (row lock, one system message, one ConversationClosed broadcast). router.post preserves ConversationThread state, so the Open/Closed badge must be derived from conversation.status (plus a local latch for the click and Echo), never from a useState that is only set on mount. Do not router.reload the thread on conversation.closed — merge the system MessageCreated row and lock the composer instead. Reverb stays in its own Coolify app; the main Dockerfile/supervisord must not start it for chat.
