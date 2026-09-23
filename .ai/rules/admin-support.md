---
paths:
  - 'resources/js/components/admin/shift-*.tsx,app/Support/WorkforcePayload.php'
---

# Admin Support

## Assigned people rows are monthly person summaries
Assigned people table rows are one person per month: avatar, name, phone, people_kind_label (Staff/Driver), assigned_dates_label (day-of-month runs), and month. Do not send role_label or legacy assignment range/shift columns on that payload. Spatie role names still load from model_has_roles by team for on-duty chips, not getRoleNames(), because Spatie roles() is scoped to the current request team (platform 0 vs branch).
