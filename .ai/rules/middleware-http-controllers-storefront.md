---
paths:
  - 'routes/public.php,app/Http/Middleware/EnsureStorefrontCustomer.php,app/Http/Controllers/Storefront/CheckoutController.php'
---

# Middleware Http Controllers Storefront

## Storefront checkout is open to guests, closed to staff
Checkout routes use ensure.storefront.customer. Guests (no customer session) pass through and order via a provisional customer resolved from the mobile number (CustomerResolutionService). Platform/branch/driver users are redirected to their dashboard. A logged-in inactive/non-customer user is logged out and sent to login. A guest whose phone belongs to a Registered account must log in (validation error on mobile). Guests never save a default address.
