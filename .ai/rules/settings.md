---
paths:
    - 'resources/js/{app.tsx,layouts/settings/**}'
---

# Settings

## Settings stay inside portal chrome

Settings pages keep the active portal shell. Map settings/* in app.tsx to SettingsPortalLayout, which wraps SettingsLayout with PlatformLayout, BranchLayout, or DriverLayout from the shared portal prop. Other guards keep AppLayout. Do not send platform, branch, or driver users through AppLayout for /settings/*.

## No Appearance settings

Appearance is removed from the settings nav and `/settings/appearance` redirects to profile. The product is light-mode only (`HandleAppearance` + `initializeTheme`). Do not restore AppearanceTabs in portal settings.

## Settings content is full-width portal UI

SettingsLayout uses the same full-width pad as other portal pages (`p-2 md:p-4`), top Profile/Security tabs (underline, not a side nav), and unconstrained content (no `max-w-xl` / `md:max-w-2xl`). Profile/Security/Delete sections use rounded border cards like other portal forms.

## Password confirm stays in portal settings

Fortify confirm-password renders `settings/confirm-password` (SettingsPortalLayout), not `auth/confirm-password`, so Security’s password.confirm gate keeps platform/branch/driver chrome.

## Profile edits name, phone, email, and avatar

Self-service `/settings/profile` covers the editable users columns: name, phone, email, avatar (FileUpload variant=avatar + remove_avatar). Do not expose user_type, branch_id, or is_active on this form — those stay admin-managed. Password and 2FA stay on Security.
