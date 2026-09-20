---
paths:
    - 'resources/js/components/admin/role-*.tsx, resources/js/pages/{platform,branch}/roles/**, app/Http/Controllers/{Platform,Branch}/RoleController.php, app/Http/Requests/{Platform,Branch}/*RoleRequest.php'
---

# Http Requests Platform Branch

## Role names unique in app and UI

Role names are unique per guard and Spatie team in Form Requests (Rule::unique scoped to team_id). Controllers pass existingNames to RoleForm for instant blur checks plus Precognition. Do not add a new unique index — keep the original permission tables migration as-is.
