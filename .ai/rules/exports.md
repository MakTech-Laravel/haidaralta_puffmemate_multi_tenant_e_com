---
paths:
    - 'app/Exports/**'
---

# Exports

## Laravel Excel 4 FromQuery exports with four scopes

Spreadsheet downloads use maatwebsite/excel ^4 (not Fast Excel and not a JS xlsx library). Export classes implement FromQuery + WithMapping + WithHeadings + Exportable; query() must return the injected Eloquent builder and must not call get(). AbstractDataTable::exportQuery(ExportScope) clones the list request: all keeps portal/tenant lock only; period keeps filter from/to only (422 if both empty); filters keeps filter[] + search + sort unpaginated; page applies forPage. Spatie QueryBuilder v7 is a wrapper — return getEloquentBuilder() for Laravel Excel. First wired on stock transactions and price history (platform + branch). Queue later with ShouldQueue on Horizon; do not add a second export package.
