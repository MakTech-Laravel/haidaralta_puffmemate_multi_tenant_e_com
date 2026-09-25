---
paths:
  - 'app/Http/Controllers/Customer/**'
---

# Controllers Customer

## Customer account writes stay on Customer stack
Customer /account mutations (profile + shipping, password, billing) go through Customer\AccountController, CustomerAccountService, and app/Http/Requests/Customer/* — not Settings ProfileController/SecurityController. Persist structured addresses on customer_addresses (unique user_id+type shipping|billing). Leave Two Factor display-only on this page.
