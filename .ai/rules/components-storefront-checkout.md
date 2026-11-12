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
One wrapping Day chip grid (4 columns on mobile, 8 from md, never a horizontal scroller) covers today through the last Late date (`deliveryDateOptions`): the today chip selects Same day, any other chip selects Late with that date. A Time grid then shows one delivery shift (Same day: remaining today shifts; Late: all active shifts). Shift buttons show the range from `start_time`/`end_time` (`shiftRangeLabel`), not a Select dropdown. CheckoutService resolves date/shift via DeliveryShiftAvailability; do not use DeliveryTimeSlot for storefront place.
