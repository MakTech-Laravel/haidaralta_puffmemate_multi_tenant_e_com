---
paths:
  - 'routes/public.php,app/Http/Middleware/EnsureStorefrontCustomer.php,app/Http/Controllers/Storefront/CheckoutController.php'
---

# Middleware Http Controllers Storefront

## Storefront checkout is open to guests, customers and staff
Checkout routes use ensure.storefront.customer. Guests (no customer session) pass through and order via a provisional customer resolved from the mobile number (CustomerResolutionService). Logged-in platform/branch/driver users are not redirected: they have no customer session, so they check out exactly like a guest (session cart, order attached by mobile number, signed public view-order link, no saved address). A logged-in inactive/non-customer user on the customer guard is logged out and sent to login. The checkout mobile must be an Australian mobile for everyone, logged in or not (Phone::validationRule(allowInternational: false)). A guest may also order with a Registered customer's phone without logging in: the order attaches to that account (its name and saved address are never changed), opens Pending/Placed by the usual "has a Delivered order" rule, and the account owner gets the delivery code and notification. A platform, branch or driver phone works the same way (phones are unique across all users, so the order attaches to that user record). Only an inactive account is rejected, with a validation error on mobile. Guests never save a default address.
