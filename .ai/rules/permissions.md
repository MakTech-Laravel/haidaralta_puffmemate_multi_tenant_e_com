---
paths:
  - 'resources/js/pages/**/permissions/**'
---

# Permissions

## Permissions indexes are an intentional DataTable exception
Platform/branch permissions stay a grouped client-search catalog, not Eloquent *DataTable. Shared PermissionGroupsTable is a two-pane directory (group nav + dense rows with key and roles count), not stacked per-group TanStack tables or DataTableToolbar. Catalog is static Spatie permissions from code/seeders — do not add Meilisearch or Spatie QueryBuilder pagination.
