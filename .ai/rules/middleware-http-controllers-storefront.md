---
paths:
  - 'routes/public.php,app/Http/Middleware/EnsureStorefrontCustomer.php,app/Http/Controllers/Storefront/CheckoutController.php'
---

# Middleware Http Controllers Storefront

## Storefront checkout is customer-only
Checkout routes use ensure.storefront.customer. Platform/branch/driver users are redirected to their dashboard; only active customer-guard customers may place orders. Do not open checkout behind auth:customer alone.
