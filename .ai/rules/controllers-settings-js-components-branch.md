---
paths:
  - 'resources/js/pages/settings/**,app/Http/Controllers/Settings/ProfileController.php,resources/js/components/branch/nav.ts'
---

# Controllers Settings Js Components Branch

## Branch profile is one page with Change Password
Branch account is one Profile page. Avatar stacks above name/phone/email. Password lives in a Change Password card on the same page, then Fortify 2FA/passkeys. GET /settings/security redirects Branch to profile before password.confirm. Sidebar has a top-level Profile item after Dashboard. User menu hides Security for Branch. No account delete. Platform keeps Settings Profile/Security tabs and the side-by-side avatar layout.
