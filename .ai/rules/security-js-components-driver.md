---
paths:
  - 'resources/js/pages/driver/security/**,resources/js/components/driver/driver-account-sheet.tsx'
---

# Security Js Components Driver

## Driver security password and Fortify 2FA
GET /driver/security is the field Security page under DriverLayout. Account sheet links there, not settings/security. Password submits to driver.security.password.update. 2FA enrolls via Fortify (Enable/Disable, QR, recovery codes with autoFetch=false and password.confirmation first). Passkeys and phone stay display-only. Do not add Security to the five-tab bar.
