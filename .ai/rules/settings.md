---
paths:
  - 'resources/js/{app.tsx,layouts/settings/**}'
---

# Settings

## Settings stay inside portal chrome
Settings pages keep the active portal shell. Map settings/* in app.tsx to SettingsPortalLayout, which wraps SettingsLayout with PlatformLayout or BranchLayout from the shared portal prop. Other guards keep AppLayout. Do not send platform or branch users through AppLayout for /settings/*.
