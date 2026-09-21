---
paths:
  - '{app/Support/ConversationInbox.php,app/Services/Conversation/**,app/Events/ConversationInboxUpdated.php,resources/js/components/conversation/**}'
---

# Components Conversation

## Inbox Echo inserts threads; default Open
ConversationInboxUpdated must include a full inbox `row` plus optional `message` and notify platform staff plus that branch's operators, not only participants — applyOverlay cannot insert unknown ids. openForOrder must notifyInbox after create. Default inbox status is `open`, not `all`. Forward inbox `message` into the open ConversationThread because presence MessageCreated is not a reliable delivery path.
