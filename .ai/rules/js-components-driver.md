---
paths:
  - 'app/Http/Controllers/Driver/OrderController.php,resources/js/pages/driver/orders/**,resources/js/components/driver/driver-order-card.tsx'
---

# Js Components Driver

## Driver chat is nested under an order
Driver conversations are order-scoped. Use GET driver/orders/{order}/chat (OrderController::chat, Inertia driver/orders/chat). Put Chat as a sibling Link on the order card and order show — never nest it inside the show Link, and never restore /driver/chats or a Chats nav tab.
