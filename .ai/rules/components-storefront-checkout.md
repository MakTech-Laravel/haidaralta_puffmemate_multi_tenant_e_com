---
paths:
  - 'resources/js/components/storefront/checkout/**'
---

# Components Storefront Checkout

## Checkout name and mobile share a row
Your details puts Full name and Mobile number on one sm:grid-cols-2 row. Do not stack them as full-width fields.

## Checkout uses Regular/Express and DB payment methods
Delivery methods are regular (default, no slot) and express (requires recommended date + time slot nested on the method). Payment methods are only enabled rows from payment_gateway_settings — never hardcode COD/POS in the checkout form. PlaceOrderRequest Rule::in enabled keys.

## Express checkout has no customer date
Express requires a preferred time slot only. Do not show a delivery date picker. CheckoutService stores delivery_date as today and ignores any client date. Regular stores null date and null slot.
