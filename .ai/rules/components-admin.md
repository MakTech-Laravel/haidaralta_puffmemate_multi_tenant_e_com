---
paths:
    - 'resources/js/pages/**/permissions/**, resources/js/components/admin/permission-groups-table.tsx'
---

# Components Admin

## Permission catalog is a group-card grid

Permissions indexes stay a grouped client-search catalog (not Eloquent DataTable). Shared PermissionGroupsTable is a stats strip plus masonry cards (`columns-*`, `break-inside-avoid`) so each group is only as tall as its permissions. Groups are ordered A–Z by name. Do not use equal-height CSS grid rows. Platform and branch pages share it.
