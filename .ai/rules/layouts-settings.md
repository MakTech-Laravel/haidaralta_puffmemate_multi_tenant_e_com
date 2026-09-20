---
paths:
  - 'resources/js/layouts/settings/**'
---

# Layouts Settings

## Driver settings keep field chrome
SettingsPortalLayout wraps DriverLayout when portal is driver so /settings/security keeps the field header and tab bar. Hide Appearance and Profile in SettingsLayout for drivers (appearance is forced light; profile edit is /driver/profile/edit). Do not send drivers through AppLayout for settings/*.

## Drivers do not use settings profile UI
Hide Profile and Appearance in SettingsLayout for drivers. GET /settings/profile redirects drivers to /driver/profile/edit. Keep /settings/security in DriverLayout via SettingsPortalLayout.
