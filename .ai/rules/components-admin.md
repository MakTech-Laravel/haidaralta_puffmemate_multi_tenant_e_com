---
paths:
  - 'resources/js/pages/**/permissions/**, resources/js/components/admin/permission-groups-table.tsx'
---

# Components Admin

## Permission catalog is a two-pane directory
Permissions indexes stay a grouped client-search catalog (not Eloquent DataTable). Shared PermissionGroupsTable is a two-pane directory (group nav + dense rows with key and roles count), not stacked per-group TanStack tables. Platform and branch pages share it.
