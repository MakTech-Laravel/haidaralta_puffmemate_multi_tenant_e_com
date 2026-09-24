---
paths:
  - 'app/Http/Middleware/EnsureAnyPortalGuard.php,routes/settings.php'
---

# App Http Middleware

## ensure.any.portal binds branch context
ensure.any.portal binds currentBranch + Spatie team from the branch user's branch_id when resolve.branch is absent (e.g. /settings/*) so SettingsPortalLayout keeps branch nav, logo, and permissions.
