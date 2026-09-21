---
paths:
    - 'resources/js/{app.tsx,layouts/settings/**}'
---

# Settings

## Settings stay inside portal chrome

Settings pages keep the active portal shell. Map settings/* in app.tsx to SettingsPortalLayout, which wraps SettingsLayout with PlatformLayout, BranchLayout, or DriverLayout from the shared portal prop. Other guards keep AppLayout. Do not send platform, branch, or driver users through AppLayout for /settings/*.
