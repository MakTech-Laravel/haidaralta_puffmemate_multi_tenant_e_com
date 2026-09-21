---
paths:
  - 'resources/js/pages/auth/**,app/Actions/Fortify/CreateNewUser.php,app/Http/Requests/Auth/StoreRegisteredUserRequest.php,resources/js/layouts/auth/**'
---

# Auth

## Public register is customer-only phone signup
Public /register accepts name, phone, password, and password_confirmation. CreateNewUser always sets user_type=customer, branch_id=null, and email=null. All portals share /login; do not add portal-specific login or register routes for staff/drivers.

## Auth logo sits on the black brand plane
Show the storefront script logo on the black auth brand plane (no white plate — the PNG already has a black backdrop). Cap height (~h-10 to lg:h-14) so the small asset is not heavily upscaled or clipped. Below lg use a compact brand strip (logo + accent only); keep eyebrow/tagline for lg+.
