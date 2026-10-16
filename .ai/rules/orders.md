---
paths:
  - 'resources/js/components/driver/driver-order-chat-panel.tsx,resources/js/pages/driver/orders/chat.tsx,resources/js/pages/customer/orders/chat.tsx,resources/js/components/conversation/order-chat-body.tsx'
---

# Orders

## Order chat panels share OrderChatBody
Driver (DriverOrderChatPanel) and customer (pages/customer/orders/chat) order chats keep their own compact header and render components/conversation/order-chat-body for the transcript + composer. Sends are an optimistic Inertia POST to driver.orders.messages.store / customer.orders.messages.store (back() reloads messages). Peers' messages arrive live via useConversationRealtime on the order's presence channel (`channel` prop = Conversation::channelName(), null skips subscribe); the viewer's own broadcast echoes are ignored. ConversationClosed latches the composer off locally. Customer chat routes use order_number and 404 unless the customer owns the order, the thread exists, the fulfillment allowsOrderChat and Gate view passes.
