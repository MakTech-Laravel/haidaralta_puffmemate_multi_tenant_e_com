---
paths:
  - 'resources/js/pages/driver/security/**,resources/js/components/driver/driver-account-sheet.tsx'
---

# Security Js Components Driver

## Driver security is a static field page
Driver Security is the static field page at GET /driver/security (driver.security.index) under DriverLayout. Account sheet links there, not settings/security. Show password inputs, two-factor, passkeys, and phone as display-only fields styled like Edit details. Do not submit a password form, enroll 2FA, or manage passkeys on this page, and do not add Security to the five-tab bar.
