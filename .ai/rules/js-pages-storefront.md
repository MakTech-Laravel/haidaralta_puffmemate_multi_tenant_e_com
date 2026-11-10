---
paths:
  - 'app/Http/Controllers/Storefront/CheckoutController.php,resources/js/pages/storefront/checkout.tsx'
  - 'app/Http/Controllers/Storefront/CheckoutController.php,routes/public.php,resources/js/pages/storefront/checkout-confirmation.tsx'
---

# Js Pages Storefront

## Checkout quotes shipping after pin and shows errors
POST /checkout/shipping stores storefront.checkout_shipping (fee + lat/long). Checkout summary uses that quote when it matches the pin; otherwise it says Calculated at checkout. Both Same day and Late require a DeliveryShift (`time_slot_id`); Late also requires `delivery_date` (tomorrow…today+14). Same day date is set server-side to today. Always render form.errors on checkout — place() validation (shift, terms, allocation) used to fail silently. Show each error directly under its own input (name, mobile, address/pin, city, postal code, delivery method, date, shift, payment); only errors with no field (`cart`, `pack`, unknown keys) use the alert at the top of the form. Dismiss a field's error as soon as the customer edits that field.

## Shipping quote is background JSON not an Inertia visit
POST /checkout/shipping returns JSON (fee + lat/long) and stores storefront.checkout_shipping. Checkout quotes with useHttp so pinning does not remount the page. Delivery method cards never show a fee; only the order summary does after a matching pin.

## Confirmation URL uses order number
GET /checkout/confirmation/{order:order_number} binds on orders.order_number (PMM-YYYY-NNNN), never the database id. Confirmation UI matches checkout cards and the order summary. Do not put order.id, branches, or fulfillments in the confirmation payload.

## Checkout allows guests, blocks staff
All /checkout routes use ensure.storefront.customer. Platform, branch, and driver sessions are redirected to their portal dashboard with a toast; guests may check out. Guest orders attach to the customer that owns the phone (an existing provisional or registered account, otherwise a new provisional one) and the order number is kept in session `storefront.guest_orders`; confirmation is visible to the owning customer or that guest session only, and `viewOrderUrl` for guests is the signed public link `orders.track` (`/order-status/{order_number}`, `OrderTrackingLink::url`), shown as View order next to Continue shopping. That page is read-only (no delivery code, driver or actions, mobile masked, noindex), needs the signature, and stays valid until 30 days after the order is Delivered or Cancelled (then 410). Every storefront checkout order (cart and pack buy-now, guest or logged in) also texts that same link to the order phone at placement via `OrderPlacementNotifier::sendViewLink()`, separate from the delivery-code SMS and wrapped in `rescue()`. Do not use auth:customer alone for checkout.
