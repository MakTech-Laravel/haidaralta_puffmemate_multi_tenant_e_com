---
paths:
  - 'app/Http/Controllers/Storefront/CheckoutController.php,resources/js/pages/storefront/checkout.tsx'
  - 'app/Http/Controllers/Storefront/CheckoutController.php,routes/public.php,resources/js/pages/storefront/checkout-confirmation.tsx'
---

# Js Pages Storefront

## Checkout quotes shipping after pin and shows errors
POST /checkout/shipping stores storefront.checkout_shipping (fee + lat/long). Checkout summary uses that quote when it matches the pin; otherwise it says Calculated at checkout. Both Same day and Late require a DeliveryShift (`time_slot_id`); Late also requires `delivery_date` (tomorrow…today+14). Same day date is set server-side to today. Always render form.errors on checkout — place() validation (shift, terms, allocation) used to fail silently.

## Shipping quote is background JSON not an Inertia visit
POST /checkout/shipping returns JSON (fee + lat/long) and stores storefront.checkout_shipping. Checkout quotes with useHttp so pinning does not remount the page. Delivery method cards never show a fee; only the order summary does after a matching pin.

## Confirmation URL uses order number
GET /checkout/confirmation/{order:order_number} binds on orders.order_number (PMM-YYYY-NNNN), never the database id. Confirmation UI matches checkout cards and the order summary. Do not put order.id, branches, or fulfillments in the confirmation payload.

## Checkout allows guests, blocks staff
All /checkout routes use ensure.storefront.customer. Platform, branch, and driver sessions are redirected to their portal dashboard with a toast; guests may check out. Guest orders attach to a provisional customer (by phone) and the order number is kept in session `storefront.guest_orders`; confirmation is visible to the owning customer or that guest session only, and `viewOrderUrl` is null for guests (hide View order). Do not use auth:customer alone for checkout.
