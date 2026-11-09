---
paths:
  - 'app/Services/Storefront/CheckoutService.php,resources/js/components/storefront/checkout/**'
---

# Storefront Checkout

## Checkout delivery methods are not free
DeliveryMethod.fee is a leftover flat amount (seeded 0). Real shipping is ShippingCalculator distance cost at place() via the matched branch (area polygon or nearest-active AU fallback). Never label method.formattedFee as FREE. Checkout cards and the order summary say Calculated at checkout. Cart quote/place use AU gate + area/nearest-active + late-delivery opt-in (see checkout.md / order.md); pack buy-now stays on the pack's branch.

## Delivery method cards omit shipping cost
DeliveryMethod.fee is leftover and unused. deliveryMethods() returns id/label/description/requiresSlot/requiresDate/minDate/maxDate/timeSlots from active platform DeliveryShift rows (not DeliveryTimeSlot). Distance cost lives in the checkout summary via the shipping quote, not on the method cards.

## Delivery keys are regular and express with DeliveryShift
Checkout delivery_methods keys are `regular` (Same day) and `express` (Late). Both require a DeliveryShift id in `time_slot_id`. Same day: omit method when no eligible shifts remain; server sets `delivery_date` to today and rejects shifts with `start_time` before now (app timezone). Late: customer date required in [tomorrow, today+`catalog.late_delivery_max_days`]; all active shifts selectable. Orders store shift label on `time_slot` and `delivery_shift_id` FK. DeliverySLACalculator still uses express/regular transfer hours for allocation engines — unrelated to shift selection.
