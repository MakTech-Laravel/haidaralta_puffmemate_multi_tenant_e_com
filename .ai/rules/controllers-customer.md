---
paths:
  - 'app/Http/Controllers/Customer/**'
---

# Controllers Customer

## Customer account writes stay on Customer stack
Customer /account mutations (profile + shipping, password, billing) go through Customer\AccountController, CustomerAccountService, and app/Http/Requests/Customer/* — not Settings ProfileController/SecurityController. Persist structured addresses on customer_addresses (unique user_id+type shipping|billing). Account 2FA is Fortify-only: enable/confirm/disable/QR/recovery use Fortify routes (`@/routes/two-factor`). Do not add a Customer 2FA service or table. Login still skips the Fortify 2FA challenge.
