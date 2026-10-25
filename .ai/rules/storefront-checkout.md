---
paths:
  - 'app/Services/Storefront/CheckoutService.php,resources/js/components/storefront/checkout/**'
---

# Storefront Checkout

## Checkout delivery methods are not free
DeliveryMethod.fee is a leftover flat amount (seeded 0). Real shipping is ShippingCalculator distance cost at place() via the area-matched branch. Never label method.formattedFee as FREE. Checkout cards and the order summary say Calculated at checkout. Cart quote/place use area polygons + late-delivery opt-in (see checkout.md / order.md); pack buy-now stays on the pack's branch.

## Delivery method cards omit shipping cost
DeliveryMethod.fee is leftover and unused. deliveryMethods() returns id/label/description/requiresSlot/timeSlots only. Distance cost lives in the checkout summary via the shipping quote, not on the method cards.

## Delivery keys are regular and express
Checkout delivery_methods keys are `regular` (sort 0, no slot) and `express` (sort 1, requires_slot, recommended 9–12/12–3/3–6/6–9 windows). Express has no customer date; CheckoutService writes delivery_date as today and stores the chosen slot label. DeliverySLACalculator uses `express_max_transfer_hours` for express/same-day and `regular_max_transfer_hours` otherwise. Do not restore australia-wide/same-day labels or an Express date picker.

## Payment methods come from payment_gateway_settings
Checkout paymentMethods are PaymentGatewaySettingsService::enabledMethods() (cod/pos/payment_link fixed keys). PlaceOrderRequest validates payment_method with Rule::in(enabled keys). Do not hardcode the checkout payment list in React.
