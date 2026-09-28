---
paths:
  - 'resources/js/{pages,components}/storefront/checkout/**'
---

# Checkout

## Checkout coupon is collapsed until asked
Do not show the coupon input by default. Start with Have a coupon code? Reveal the field + Apply only after that click. After a successful apply, show the code and a Remove action that DELETEs /checkout/coupon. Every checkout Input/Select/DatePicker must have a placeholder. Delivery methods use radio cards with Choose one; time slots sit under Available time slots with required date + slot; payment methods are compact badge cards (COD $, POS EFT) with No online card entry — not stacked radios.

## Place order stays disabled until required checkout fields
Checkout Place order is disabled until required fields are filled: name, mobile, address, city, pin lat/long, delivery and payment methods, terms, and same-day date + slot. Do not leave the button clickable with empty required inputs.
