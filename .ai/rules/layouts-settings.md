---
paths:
  - 'resources/js/layouts/settings/**'
---

# Layouts Settings

## Driver settings keep field chrome
SettingsPortalLayout wraps DriverLayout when portal is driver so /settings/security keeps the field header and tab bar. Hide Profile in SettingsLayout for drivers (profile edit is /driver/profile/edit). Appearance is gone for every portal. Do not send drivers through AppLayout for settings/*.

## Drivers do not use settings profile UI
Hide Profile in SettingsLayout for drivers. GET /settings/profile redirects drivers to /driver/profile/edit. Keep /settings/security and /settings/confirm-password in DriverLayout via SettingsPortalLayout.

## Settings nav is top tabs
Profile/Security in SettingsLayout are top underline tabs (border-b), not a left sidebar. Hide Profile for drivers. User menu labels the security link Security (Shield), not Settings.
