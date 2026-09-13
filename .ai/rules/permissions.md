---
paths:
  - 'resources/js/pages/**/permissions/**'
---

# Permissions

## Permissions indexes are an intentional DataTable exception
Platform/branch permissions stay grouped (group → permissions cards), not Eloquent *DataTable. Use shared PermissionGroupsTable (DataTableToolbar + in-memory TanStack rows). Catalog is static Spatie permissions from code/seeders — do not add Meilisearch or Spatie QueryBuilder pagination.
