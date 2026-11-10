---
paths:
  - 'app/Http/Controllers/Driver/OrderController.php,resources/js/pages/driver/orders/**,resources/js/components/driver/driver-order-card.tsx'
---

# Js Components Driver

## Order chat stays nested under an order
Customer-facing order conversations stay order-scoped: GET driver/orders/{order}/chat (OrderController::chat, Inertia driver/orders/chat). Put Chat as a sibling Link on the order card and order show — never nest it inside the show Link.

## Branch ops chat is a separate inbox
Branch↔driver internal chat (no order/customer) lives at /driver/chats (DriverChatController). It may appear in the desktop sidebar; keep the five field tabs focused on dashboard/orders/route/earnings/place-order.
