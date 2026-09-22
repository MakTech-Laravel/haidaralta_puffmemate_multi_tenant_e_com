---
paths:
  - 'resources/js/components/admin/shift-*.tsx,app/Support/WorkforcePayload.php'
---

# Admin Support

## Scheduled people show role and type filter
Scheduled people rows show Spatie role_label (Staff, Super admin, …) or Driver. Roster toolbar filters client-side All people | Staff | Drivers by user_type (not Meilisearch). Role names are loaded from model_has_roles by team, not getRoleNames(), because Spatie roles() is scoped to the current request team (platform 0 vs branch).
