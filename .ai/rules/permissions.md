---
paths:
    - 'resources/js/pages/**/permissions/**'
---

# Permissions

## Permissions indexes are an intentional DataTable exception

Platform/branch permissions stay a grouped client-search catalog, not Eloquent *DataTable. Groups are ordered A–Z by name. Shared PermissionGroupsTable is a stats strip plus masonry group cards (`columns-*` + `break-inside-avoid`) so short groups do not stretch to match tall ones. Do not use a two-pane picker, stacked TanStack tables, or DataTableToolbar. Catalog is static Spatie permissions from code/seeders — do not add Meilisearch or Spatie QueryBuilder pagination.
