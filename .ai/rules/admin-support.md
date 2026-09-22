---
paths:
  - 'resources/js/components/admin/shift-*.tsx,app/Support/WorkforcePayload.php'
---

# Admin Support

## Assigned people rows omit role labels
Assigned people table rows are name, dates, and shift. Do not send role_label or user_type_label on that payload. Spatie role names still load from model_has_roles by team for on-duty chips, not getRoleNames(), because Spatie roles() is scoped to the current request team (platform 0 vs branch).
