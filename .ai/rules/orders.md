---
paths:
  - 'resources/js/components/driver/driver-order-chat-panel.tsx,resources/js/pages/driver/orders/chat.tsx'
---

# Orders

## Driver chat panel reuses conversation bubbles
Driver order chat UI is DriverOrderChatPanel: compact stop header (back, customer, status, phone), ConversationMessageRow transcript, ConversationComposer. Demo transcript comes from DemoCatalog::driverOrderChatMessages until real order conversations exist. Local send only; no Reverb endpoints yet.
