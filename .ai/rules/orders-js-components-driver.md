---
paths:
  - 'resources/js/pages/driver/orders/**,resources/js/components/driver/driver-order-card.tsx'
---

# Orders Js Components Driver

## Orders use field status chips and a map pin
Driver orders filters are field chips: Active (default), On the way, Arrived, Delivered, All. Do not add warehouse states such as picked or packed. The map pin on a card is a sibling of Chat and goes to /driver/route?highlight={orderId}. Do not nest it inside the order show Link.

## Driver order cards stay compact with search
Driver order list cards stay compact: name, status, one-line address, order number/window/total. Search covers order number, customer, suburb, address, and phone. Do not restore the dense product/notes/payment stack on the list card.

## Driver order show surfaces discount in the products footer
Driver order show must include subtotal, discount (with coupon code when present), and delivery fee above the collect/total row so drivers can see when a coupon was applied.
