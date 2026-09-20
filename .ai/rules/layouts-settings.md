---
paths:
  - 'resources/js/layouts/settings/**'
---

# Layouts Settings

## Driver settings keep field chrome
SettingsPortalLayout wraps DriverLayout when portal is driver so /settings/profile and /settings/security keep the field header and tab bar. Hide Appearance in SettingsLayout for drivers (appearance is forced light). Do not send drivers through AppLayout for settings/*.
