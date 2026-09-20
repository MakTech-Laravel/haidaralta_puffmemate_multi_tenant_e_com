---
paths:
  - 'resources/js/pages/driver/orders/**,resources/js/components/driver/driver-order-card.tsx'
---

# Orders Js Components Driver

## Orders use field status chips and a map pin
Driver orders filters are field chips: Active (default), On the way, Arrived, Delivered, All. Do not add warehouse states such as picked or packed. The map pin on a card is a sibling of Chat and goes to /driver/route?highlight={orderId}. Do not nest it inside the order show Link.
