---
paths:
  - 'resources/js/pages/auth/**,app/Actions/Fortify/CreateNewUser.php,app/Http/Requests/Auth/StoreRegisteredUserRequest.php,resources/js/layouts/auth/**'
---

# Auth

## Public register is customer-only phone signup
Public /register accepts name, phone, password, and password_confirmation. CreateNewUser always sets user_type=customer, branch_id=null, and email=null. All portals share /login; do not add portal-specific login or register routes for staff/drivers.

## Auth logo sits on the near-black brand plane
The auth brand plane is `bg-ink-surface` (near-black, with a soft blue glow). The default script logo has dark lettering, so it keeps a white plate; a custom uploaded logo sits directly on the plane. Cap height (~h-10 to lg:h-14) so the small asset is not heavily upscaled or clipped. Below lg use a compact brand strip (logo + accent only); keep eyebrow/tagline for lg+.
