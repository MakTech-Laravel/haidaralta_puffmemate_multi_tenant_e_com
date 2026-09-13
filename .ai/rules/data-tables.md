---
paths:
  - 'app/DataTables/**'
---

# Data Tables

## Spatie QueryBuilder DataTables
List indexes use Spatie QueryBuilder via App\DataTables\*DataTable extending AbstractDataTable. URL: filter[key], sort=-col, per_page. Models may use HasDataTableConfig ($dataTableSearchable/Sortable/Filterable). SoftDeletes lists use AllowedFilter::trashed() (with|only). Frontend: @/components/data-table with TanStack v8 + Spatie query keys via useDataTableQuery. Do not add niche datatable packages.
