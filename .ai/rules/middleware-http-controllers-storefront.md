---
paths:
  - 'routes/public.php,app/Http/Middleware/EnsureStorefrontCustomer.php,app/Http/Controllers/Storefront/CheckoutController.php'
---

# Middleware Http Controllers Storefront

## Storefront checkout is open to guests, closed to staff
Checkout routes use ensure.storefront.customer. Guests (no customer session) pass through and order via a provisional customer resolved from the mobile number (CustomerResolutionService). Platform/branch/driver users are redirected to their dashboard. A logged-in inactive/non-customer user is logged out and sent to login. A guest may also order with a Registered customer's phone without logging in: the order attaches to that account (its name and saved address are never changed), opens Pending/Placed by the usual "has a Delivered order" rule, and the account owner gets the delivery code and notification. A staff/driver phone or an inactive account is rejected with a validation error on mobile. Guests never save a default address.
