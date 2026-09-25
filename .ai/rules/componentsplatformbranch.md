---
paths:
  - 'resources/js/pages/settings/**,app/Http/Controllers/Settings/ProfileController.php,resources/js/components/{platform,branch}/nav.ts'
---

# Componentsplatformbranch

## Platform and Branch share one Profile page
Platform and Branch use one Profile page: avatar is a smaller block on the left, then name/phone/email, Change Password, Fortify 2FA/passkeys. No Settings tabs. GET /settings/security redirects both before password.confirm. Sidebar has top-level Profile after Dashboard. User menu hides Security. No account delete. Customer and driver stay unchanged.
