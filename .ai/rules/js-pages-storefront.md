---
paths:
  - 'app/Http/Controllers/Storefront/CheckoutController.php,resources/js/pages/storefront/checkout.tsx'
  - 'app/Http/Controllers/Storefront/CheckoutController.php,routes/public.php,resources/js/pages/storefront/checkout-confirmation.tsx'
---

# Js Pages Storefront

## Checkout quotes shipping after pin and shows errors
POST /checkout/shipping stores storefront.checkout_shipping (fee + lat/long). Checkout summary uses that quote when it matches the pin; otherwise it says Calculated at checkout. Same-day requires delivery_date. Always render form.errors on checkout — place() validation (date, terms, allocation) used to fail silently.

## Shipping quote is background JSON not an Inertia visit
POST /checkout/shipping returns JSON (fee + lat/long) and stores storefront.checkout_shipping. Checkout quotes with useHttp so pinning does not remount the page. Delivery method cards never show a fee; only the order summary does after a matching pin.

## Confirmation URL uses order number
GET /checkout/confirmation/{order:order_number} binds on orders.order_number (PMM-YYYY-NNNN), never the database id. Confirmation UI matches checkout cards and the order summary. Do not put order.id, branches, or fulfillments in the confirmation payload.
