---
paths:
  - 'resources/js/components/storefront/checkout/**'
---

# Components Storefront Checkout

## Checkout name and mobile share a row
Your details puts Full name and Mobile number on one sm:grid-cols-2 row. Do not stack them as full-width fields.

## Checkout uses Regular/Express and DB payment methods
Delivery methods are regular (Same day: shift only) and express (Late: date + shift). Shifts come from platform DeliveryShift nested on the method as `timeSlots`. Payment methods are only enabled rows from payment_gateway_settings — never hardcode COD/POS in the checkout form. PlaceOrderRequest Rule::in enabled keys.

## Late delivery has a customer date; Same day does not
Express/Late shows DatePicker (`minDate`/`maxDate` from props) then one delivery shift. Same day shows remaining today shifts only — no date picker. CheckoutService resolves date/shift via DeliveryShiftAvailability; do not use DeliveryTimeSlot for storefront place.
